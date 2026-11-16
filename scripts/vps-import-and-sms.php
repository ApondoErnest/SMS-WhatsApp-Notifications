<?php

/**
 * One-shot production helper (run inside the app container).
 *
 * Imports NZEUGA ROLAND / LT 003 MM under NOVETESCO Mendong Yaounde
 * and sends an SMS immediately to +237672671221.
 *
 * Usage on VPS:
 *   docker compose -f docker-compose.prod.yml --env-file .env.docker exec -T app \
 *     php scripts/vps-import-and-sms.php
 */

use App\Models\ImportBatch;
use App\Models\InspectionCenter;
use App\Models\InspectionRecord;
use App\Models\User;
use App\Services\CsvImport\DuplicateDetectionService;
use App\Services\Notification\MessagePersonalizer;
use App\Services\Notification\MessagingConsentService;
use App\Services\Notification\NotificationSchedulerService;
use App\Services\Sms\NexahSmsService;
use App\Support\PhoneNormalizer;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$center = InspectionCenter::query()
    ->where('name', 'like', '%Mendong%')
    ->orWhere('name', 'like', '%NOVETESCO%')
    ->orderBy('id')
    ->first();

if (! $center) {
    fwrite(STDERR, "Center not found (looked for Mendong / NOVETESCO).\n");
    fwrite(STDERR, "Existing centers:\n");
    foreach (InspectionCenter::orderBy('name')->get(['id', 'name']) as $c) {
        fwrite(STDERR, "  #{$c->id} {$c->name}\n");
    }
    exit(1);
}

echo "Center: #{$center->id} {$center->name}\n";

// Original CSV had an empty expiration date — that fails validation.
// Use 16/12/2026 so a 30-day reminder lands on 16/11/2026 (today).
$registrationDate = '2026-10-15';
$inspectionDate = '2026-10-15';
$expirationDate = '2026-12-16';
$licencePlate = 'LT 003 MM';
$customerName = 'NZEUGA ROLAND';
$phoneRaw = '672671221';
$status = 'Inspection acceptée';

$normalizer = app(PhoneNormalizer::class);
$phone = $normalizer->normalize($phoneRaw);
if (! $phone) {
    fwrite(STDERR, "Invalid phone: {$phoneRaw}\n");
    exit(1);
}

$duplicates = app(DuplicateDetectionService::class);
$hash = $duplicates->generateHash(
    $licencePlate,
    $inspectionDate,
    $expirationDate,
    $phone,
    $status,
);

if ($duplicates->isDuplicate($hash)) {
    $record = InspectionRecord::where('record_hash', $hash)->first();
    echo "Record already exists #{$record->id} (duplicate hash) — reusing.\n";
} else {
    $uploader = User::query()->where('role', 'super-admin')->orderBy('id')->first()
        ?? User::query()->orderBy('id')->first();

    $batch = ImportBatch::create([
        'center_id' => $center->id,
        'uploaded_by' => $uploader?->id,
        'filename' => 'imports/manual-vps-import.csv',
        'original_filename' => 'manual-vps-import.csv',
        'total_rows' => 1,
        'imported_rows' => 1,
        'duplicate_rows' => 0,
        'failed_rows' => 0,
        'processed_rows' => 1,
        'status' => 'completed',
    ]);

    $record = InspectionRecord::create([
        'center_id' => $center->id,
        'import_batch_id' => $batch->id,
        'registration_date' => $registrationDate,
        'inspection_date' => $inspectionDate,
        'expiration_date' => $expirationDate,
        'vehicle_class' => 'VL',
        'inspection_type' => 'C',
        'licence_plate' => mb_strtoupper($licencePlate),
        'vehicle_category' => 'B - VEHICULES PARTICULIERS',
        'customer_name' => $customerName,
        'phone_number' => $phoneRaw,
        'normalized_phone_number' => $phone,
        'status' => $status,
        'record_hash' => $hash,
    ]);

    echo "Imported record #{$record->id}\n";
}

app(MessagingConsentService::class)->ensureImportConsent($center->id, $phone);
$created = app(NotificationSchedulerService::class)->generateForRecord($record->fresh(['center']));
echo "Schedules created/ensured: {$created}\n";

$personalizer = app(MessagePersonalizer::class);
$template = $personalizer->resolveSmsTemplate($center->id, $center->notificationLanguage());
if (! $template) {
    fwrite(STDERR, "No active SMS template for this center/language.\n");
    exit(1);
}

$message = $personalizer->render($template->content, $record->fresh(['center']));
echo "SMS preview:\n{$message}\n\n";

$sms = app(NexahSmsService::class);
if (! $sms->isConfigured()) {
    fwrite(STDERR, "Nexah SMS is not configured in the container env.\n");
    exit(1);
}

try {
    $result = $sms->send($phone, $message);
    echo "SMS sent OK\n";
    echo 'provider_message_id='.$result['provider_message_id']."\n";
    $balance = data_get($result, 'response.sms.0.balance');
    if ($balance !== null) {
        echo "balance_from_send={$balance}\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'SMS send failed: '.$e->getMessage()."\n");
    exit(1);
}

try {
    $bal = $sms->getBalance(false);
    echo 'balance_cached='.$bal['credit'].' stale='.(($bal['stale'] ?? false) ? 'yes' : 'no')."\n";
} catch (Throwable $e) {
    echo 'balance_check_failed='.$e->getMessage()."\n";
}

echo "Done.\n";
