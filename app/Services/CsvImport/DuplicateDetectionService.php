<?php

namespace App\Services\CsvImport;

use App\Models\InspectionRecord;

class DuplicateDetectionService
{
    /**
     * Generate a unique hash from the fields that define a unique inspection record.
     *
     * Intentionally center-agnostic so the same inspection cannot exist in more than one center.
     */
    public function generateHash(
        string $licencePlate,
        ?string $inspectionDate,
        string $expirationDate,
        string $phoneNumber,
        string $status,
    ): string {
        $raw = implode('|', [
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

    /**
     * @param  list<string>  $hashes
     * @return list<string>
     */
    public function existingHashes(array $hashes): array
    {
        if ($hashes === []) {
            return [];
        }

        return InspectionRecord::query()
            ->whereIn('record_hash', $hashes)
            ->pluck('record_hash')
            ->all();
    }
}
