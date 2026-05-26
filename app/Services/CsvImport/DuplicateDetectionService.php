<?php

namespace App\Services\CsvImport;

use App\Models\InspectionRecord;

class DuplicateDetectionService
{
    /**
     * Generate a unique hash from the fields that define a unique inspection record.
     */
    public function generateHash(
        int $centerId,
        string $licencePlate,
        ?string $inspectionDate,
        string $expirationDate,
        string $phoneNumber,
        string $status,
    ): string {
        $raw = implode('|', [
            $centerId,
            mb_strtoupper(trim($licencePlate)),
            $inspectionDate ?? '',
            $expirationDate,
            $phoneNumber,
            mb_strtolower(trim($status)),
        ]);

        return hash('sha256', $raw);
    }

    public function isDuplicate(string $recordHash): bool
    {
        return InspectionRecord::where('record_hash', $recordHash)->exists();
    }
}
