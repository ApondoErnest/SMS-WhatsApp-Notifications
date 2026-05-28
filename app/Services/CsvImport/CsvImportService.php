<?php

namespace App\Services\CsvImport;

use App\Models\FailedImportRow;
use App\Models\ImportBatch;
use App\Models\InspectionRecord;
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
    ) {}

    public function process(ImportBatch $batch): void
    {
        $filePath = Storage::disk('local')->path($batch->filename);

        if (! file_exists($filePath)) {
            $batch->update(['status' => 'failed']);
            return;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $batch->update(['status' => 'failed']);
            return;
        }

        try {
            $headerLine = fgetcsv($handle, 0, ';', '"', '');

            if ($headerLine === false || $headerLine === null) {
                $batch->update(['status' => 'failed']);
                return;
            }

            $headerLine = array_map(fn ($h) => trim($h, "\xEF\xBB\xBF \t\n\r\0\x0B"), $headerLine);
            $columnMap = $this->mapHeaders($headerLine);

            if ($columnMap === null) {
                $batch->update(['status' => 'failed']);
                FailedImportRow::create([
                    'center_id' => $batch->center_id,
                    'import_batch_id' => $batch->id,
                    'row_number' => 0,
                    'row_data' => $headerLine,
                    'error_message' => 'Invalid or missing CSV headers. Expected: ' . implode('; ', self::EXPECTED_HEADERS),
                ]);
                return;
            }

            $rowNumber = 1;
            $imported = 0;
            $duplicates = 0;
            $failed = 0;
            $totalRows = 0;
            $createdRecords = [];

            while (($row = fgetcsv($handle, 0, ';', '"', '')) !== false) {
                $rowNumber++;

                if (count($row) === 1 && trim($row[0]) === '') {
                    continue;
                }

                $mapped = $this->mapRow($row, $columnMap, $headerLine);

                if ($this->isSkippedStatus($mapped['status'] ?? '')) {
                    continue;
                }

                $totalRows++;
                $errors = $this->validateRow($mapped, $rowNumber);

                if (! empty($errors)) {
                    $failed++;
                    FailedImportRow::create([
                        'center_id' => $batch->center_id,
                        'import_batch_id' => $batch->id,
                        'row_number' => $rowNumber,
                        'row_data' => $mapped,
                        'error_message' => implode('; ', $errors),
                    ]);
                    continue;
                }

                $normalizedPhone = $this->phoneService->normalize(
                    $mapped['phone_number'],
                    config('import.default_phone_country', 'CM')
                );

                if ($normalizedPhone === null) {
                    $failed++;
                    FailedImportRow::create([
                        'center_id' => $batch->center_id,
                        'import_batch_id' => $batch->id,
                        'row_number' => $rowNumber,
                        'row_data' => $mapped,
                        'error_message' => 'Invalid phone number: ' . $mapped['phone_number'],
                    ]);
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

                if ($this->duplicateService->isDuplicate($recordHash)) {
                    $duplicates++;
                    continue;
                }

                try {
                    $record = InspectionRecord::create([
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
                    ]);

                    $createdRecords[] = $record;
                    $imported++;
                } catch (\Illuminate\Database\QueryException $e) {
                    if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                        $duplicates++;
                    } else {
                        $failed++;
                        FailedImportRow::create([
                            'center_id' => $batch->center_id,
                            'import_batch_id' => $batch->id,
                            'row_number' => $rowNumber,
                            'row_data' => $mapped,
                            'error_message' => 'Database error: ' . $e->getMessage(),
                        ]);
                    }
                }
            }

            $batch->update([
                'total_rows' => $totalRows,
                'imported_rows' => $imported,
                'duplicate_rows' => $duplicates,
                'failed_rows' => $failed,
                'status' => 'completed',
            ]);

            $this->schedulerService->generateForMany($createdRecords);

        } finally {
            fclose($handle);
        }
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
                if (in_array(self::HEADER_MAP[$normalized], $requiredFields)) {
                    $requiredFound++;
                }
            }
        }

        if ($requiredFound < count($requiredFields)) {
            return null;
        }

        return $map;
    }

    private function mapRow(array $row, array $columnMap, array $headers): array
    {
        $mapped = [];
        foreach ($columnMap as $index => $field) {
            $mapped[$field] = isset($row[$index]) ? trim($row[$index]) : '';
        }

        return $mapped;
    }

    private function validateRow(array $row, int $rowNumber): array
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
            $errors[] = 'Expiration date is invalid: ' . $row['expiration_date'];
        }

        if (! empty($row['inspection_date'] ?? '') && $this->parseDate($row['inspection_date']) === null) {
            $errors[] = 'Inspection date is invalid: ' . $row['inspection_date'];
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
