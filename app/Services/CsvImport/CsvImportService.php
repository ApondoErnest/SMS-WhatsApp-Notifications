<?php

namespace App\Services\CsvImport;

use App\Models\FailedImportRow;
use App\Models\ImportBatch;
use App\Models\InspectionRecord;
use App\Services\Notification\MessagingConsentService;
use App\Services\Notification\NotificationSchedulerService;
use App\Services\Phone\PhoneNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CsvImportService
{
    private const EXPECTED_HEADERS = [
        'Regitration date',
        'Inspection date',
        'Expiration date',
        'Cat.',
        'Type',
        'Licence plate',
        'Category',
        'Customer',
        'Phone number',
        'Status',
    ];

    private const HEADER_MAP = [
        // English headers
        'regitration date' => 'registration_date',
        'registration date' => 'registration_date',
        'inspection date' => 'inspection_date',
        'expiration date' => 'expiration_date',
        'cat.' => 'vehicle_class',
        'cat' => 'vehicle_class',
        'type' => 'inspection_type',
        'licence plate' => 'licence_plate',
        'license plate' => 'licence_plate',
        'category' => 'vehicle_category',
        'customer' => 'customer_name',
        'phone number' => 'phone_number',
        'status' => 'status',
        // French headers
        'date enregistrement' => 'registration_date',
        'date inspection' => 'inspection_date',
        'date expiration' => 'expiration_date',
        'immatriculation' => 'licence_plate',
        'catégorie' => 'vehicle_category',
        'categorie' => 'vehicle_category',
        'client' => 'customer_name',
        'n° téléphone' => 'phone_number',
        'n° telephone' => 'phone_number',
        'no téléphone' => 'phone_number',
        'no telephone' => 'phone_number',
        'téléphone' => 'phone_number',
        'telephone' => 'phone_number',
        'statut' => 'status',
    ];

    public function __construct(
        private PhoneNumberService $phoneService,
        private DuplicateDetectionService $duplicateService,
        private NotificationSchedulerService $schedulerService,
        private MessagingConsentService $consentService,
    ) {}

    public function process(ImportBatch $batch): void
    {
        $filePath = Storage::disk('local')->path($batch->filename);

        if (! file_exists($filePath)) {
            $batch->update([
                'status' => 'failed',
                'error_message' => 'Uploaded file is missing on disk.',
            ]);

            return;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $batch->update([
                'status' => 'failed',
                'error_message' => 'Unable to open uploaded CSV file.',
            ]);

            return;
        }

        $maxRows = max(1, (int) config('import.max_rows', 250000));
        $chunkSize = max(50, (int) config('import.insert_chunk_size', 500));
        $progressEvery = max(100, (int) config('import.progress_every', 1000));
        $maxFailedLogs = max(0, (int) config('import.max_error_log_entries', 1000));
        $delimiter = (string) config('import.csv_delimiter', ';');

        try {
            $headerLine = fgetcsv($handle, 0, $delimiter, '"', '');

            if ($headerLine === false || $headerLine === null) {
                $batch->update([
                    'status' => 'failed',
                    'error_message' => 'CSV file is empty or unreadable.',
                ]);

                return;
            }

            $headerLine = array_map(fn ($h) => trim($h, "\xEF\xBB\xBF \t\n\r\0\x0B"), $headerLine);
            $columnMap = $this->mapHeaders($headerLine);

            if ($columnMap === null) {
                $batch->update([
                    'status' => 'failed',
                    'error_message' => 'Invalid or missing CSV headers. Expected: '.implode('; ', self::EXPECTED_HEADERS),
                ]);
                FailedImportRow::create([
                    'center_id' => $batch->center_id,
                    'import_batch_id' => $batch->id,
                    'row_number' => 0,
                    'row_data' => $headerLine,
                    'error_message' => 'Invalid or missing CSV headers. Expected: '.implode('; ', self::EXPECTED_HEADERS),
                ]);

                return;
            }

            $rowNumber = 1;
            $imported = 0;
            $duplicates = 0;
            $failed = 0;
            $totalRows = 0;
            $failedLogsStored = 0;
            $hitRowLimit = false;
            $seenHashes = [];
            $insertBuffer = [];
            $failedBuffer = [];
            $now = now();

            $flushInserts = function () use (
                &$insertBuffer,
                &$imported,
                &$duplicates,
                $batch,
                $chunkSize,
            ): void {
                if ($insertBuffer === []) {
                    return;
                }

                $hashes = array_column($insertBuffer, 'record_hash');
                $existing = array_flip($this->duplicateService->existingHashes($hashes));

                $toInsert = [];
                $phones = [];

                foreach ($insertBuffer as $row) {
                    if (isset($existing[$row['record_hash']])) {
                        $duplicates++;

                        continue;
                    }

                    $existing[$row['record_hash']] = true;
                    $toInsert[] = $row;
                    $phones[] = $row['normalized_phone_number'];
                }

                foreach (array_chunk($toInsert, $chunkSize) as $chunk) {
                    try {
                        InspectionRecord::query()->insert($chunk);
                        $imported += count($chunk);
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Fallback: insert row-by-row so unique collisions become duplicates.
                        foreach ($chunk as $row) {
                            try {
                                InspectionRecord::query()->insert([$row]);
                                $imported++;
                            } catch (\Illuminate\Database\QueryException $inner) {
                                if (str_contains($inner->getMessage(), 'Duplicate') || str_contains($inner->getMessage(), 'UNIQUE')) {
                                    $duplicates++;
                                } else {
                                    throw $inner;
                                }
                            }
                        }
                    }
                }

                $this->consentService->ensureImportConsentMany($batch->center_id, $phones);
                $insertBuffer = [];
            };

            $flushFailed = function () use (&$failedBuffer): void {
                if ($failedBuffer === []) {
                    return;
                }

                foreach (array_chunk($failedBuffer, 500) as $chunk) {
                    FailedImportRow::query()->insert($chunk);
                }

                $failedBuffer = [];
            };

            while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $rowNumber++;

                if (count($row) === 1 && trim((string) $row[0]) === '') {
                    continue;
                }

                $mapped = $this->mapRow($row, $columnMap);

                if ($this->isSkippedStatus($mapped['status'] ?? '')) {
                    continue;
                }

                if ($totalRows >= $maxRows) {
                    $hitRowLimit = true;
                    break;
                }

                $totalRows++;

                $errors = $this->validateRow($mapped);

                if ($errors !== []) {
                    $failed++;
                    if ($failedLogsStored < $maxFailedLogs) {
                        $failedBuffer[] = [
                            'center_id' => $batch->center_id,
                            'import_batch_id' => $batch->id,
                            'row_number' => $rowNumber,
                            'row_data' => json_encode($mapped, JSON_UNESCAPED_UNICODE),
                            'error_message' => implode('; ', $errors),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $failedLogsStored++;
                    }

                    if (count($failedBuffer) >= $chunkSize) {
                        $flushFailed();
                    }

                    if ($totalRows % $progressEvery === 0) {
                        $this->touchProgress($batch, $totalRows, $imported, $duplicates, $failed);
                    }

                    continue;
                }

                $normalizedPhone = $this->phoneService->normalize(
                    $mapped['phone_number'],
                    config('import.default_phone_country', 'CM')
                );

                if ($normalizedPhone === null) {
                    $failed++;
                    if ($failedLogsStored < $maxFailedLogs) {
                        $failedBuffer[] = [
                            'center_id' => $batch->center_id,
                            'import_batch_id' => $batch->id,
                            'row_number' => $rowNumber,
                            'row_data' => json_encode($mapped, JSON_UNESCAPED_UNICODE),
                            'error_message' => 'Invalid phone number: '.$mapped['phone_number'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $failedLogsStored++;
                    }

                    if (count($failedBuffer) >= $chunkSize) {
                        $flushFailed();
                    }

                    continue;
                }

                $registrationDate = $this->parseDate($mapped['registration_date'] ?? '');
                $inspectionDate = $this->parseDate($mapped['inspection_date'] ?? '');
                $expirationDate = $this->parseDate($mapped['expiration_date']);

                $recordHash = $this->duplicateService->generateHash(
                    $batch->center_id,
                    $mapped['licence_plate'],
                    $inspectionDate,
                    $expirationDate,
                    $normalizedPhone,
                    $mapped['status'],
                );

                if (isset($seenHashes[$recordHash])) {
                    $duplicates++;

                    continue;
                }

                $seenHashes[$recordHash] = true;

                $insertBuffer[] = [
                    'center_id' => $batch->center_id,
                    'import_batch_id' => $batch->id,
                    'registration_date' => $registrationDate,
                    'inspection_date' => $inspectionDate,
                    'expiration_date' => $expirationDate,
                    'vehicle_class' => trim($mapped['vehicle_class'] ?? '') ?: null,
                    'inspection_type' => trim($mapped['inspection_type'] ?? '') ?: null,
                    'licence_plate' => mb_strtoupper(trim($mapped['licence_plate'])),
                    'vehicle_category' => trim($mapped['vehicle_category'] ?? '') ?: null,
                    'customer_name' => trim($mapped['customer_name']),
                    'phone_number' => $mapped['phone_number'],
                    'normalized_phone_number' => $normalizedPhone,
                    'status' => trim($mapped['status']),
                    'record_hash' => $recordHash,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($insertBuffer) >= $chunkSize) {
                    $flushInserts();
                }

                if ($totalRows % $progressEvery === 0) {
                    $flushInserts();
                    $flushFailed();
                    $this->touchProgress($batch, $totalRows, $imported, $duplicates, $failed);
                }
            }

            $flushInserts();
            $flushFailed();

            $this->touchProgress($batch, $totalRows, $imported, $duplicates, $failed);

            $this->generateSchedulesForBatch($batch);

            $batch->update([
                'total_rows' => $totalRows,
                'imported_rows' => $imported,
                'duplicate_rows' => $duplicates,
                'failed_rows' => $failed,
                'processed_rows' => $totalRows,
                'status' => 'completed',
                'error_message' => $hitRowLimit
                    ? __('Import stopped after :max rows (file exceeded the configured limit).', ['max' => number_format($maxRows)])
                    : null,
            ]);
        } catch (\Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            throw $e;
        } finally {
            fclose($handle);
        }
    }

    private function generateSchedulesForBatch(ImportBatch $batch): void
    {
        $center = $batch->center;
        $chunkSize = max(50, (int) config('import.schedule_chunk_size', 200));

        if (! $center) {
            return;
        }

        InspectionRecord::query()
            ->where('import_batch_id', $batch->id)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($records) use ($center): void {
                foreach ($records as $record) {
                    $record->setRelation('center', $center);
                    $this->schedulerService->generateForRecord($record);
                }
            });
    }

    private function touchProgress(
        ImportBatch $batch,
        int $totalRows,
        int $imported,
        int $duplicates,
        int $failed,
    ): void {
        $batch->update([
            'total_rows' => $totalRows,
            'imported_rows' => $imported,
            'duplicate_rows' => $duplicates,
            'failed_rows' => $failed,
            'processed_rows' => $totalRows,
            'status' => 'processing',
        ]);
    }

    private function mapHeaders(array $headers): ?array
    {
        $map = [];
        $requiredFound = 0;
        $requiredFields = ['licence_plate', 'customer_name', 'phone_number', 'expiration_date', 'status'];

        foreach ($headers as $index => $header) {
            $normalized = mb_strtolower(trim($header));
            if (isset(self::HEADER_MAP[$normalized])) {
                $map[$index] = self::HEADER_MAP[$normalized];
                if (in_array(self::HEADER_MAP[$normalized], $requiredFields, true)) {
                    $requiredFound++;
                }
            }
        }

        if ($requiredFound < count($requiredFields)) {
            return null;
        }

        return $map;
    }

    private function mapRow(array $row, array $columnMap): array
    {
        $mapped = [];
        foreach ($columnMap as $index => $field) {
            $mapped[$field] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }

        return $mapped;
    }

    private function validateRow(array $row): array
    {
        $errors = [];

        if (empty($row['licence_plate'] ?? '')) {
            $errors[] = 'Licence plate is empty';
        }

        if (empty($row['customer_name'] ?? '')) {
            $errors[] = 'Customer name is empty';
        }

        if (empty($row['phone_number'] ?? '')) {
            $errors[] = 'Phone number is empty';
        }

        if (empty($row['status'] ?? '')) {
            $errors[] = 'Status is empty';
        }

        if (empty($row['expiration_date'] ?? '')) {
            $errors[] = 'Expiration date is missing';
        } elseif ($this->parseDate($row['expiration_date']) === null) {
            $errors[] = 'Expiration date is invalid: '.$row['expiration_date'];
        }

        if (! empty($row['inspection_date'] ?? '') && $this->parseDate($row['inspection_date']) === null) {
            $errors[] = 'Inspection date is invalid: '.$row['inspection_date'];
        }

        return $errors;
    }

    private function isSkippedStatus(string $status): bool
    {
        $normalized = mb_strtolower(trim($status));

        return in_array($normalized, [
            'cancelled',
            'canceled',
            'annulé',
            'annule',
        ], true);
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.Y', 'Y/m/d'];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }
}
