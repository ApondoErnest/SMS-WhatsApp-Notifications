<?php

use App\Models\InspectionRecord;
use App\Services\CsvImport\DuplicateDetectionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_records', function (Blueprint $table) {
            $table->dropUnique(['record_hash']);
        });

        $duplicates = app(DuplicateDetectionService::class);
        $seen = [];

        InspectionRecord::query()
            ->orderBy('id')
            ->cursor()
            ->each(function (InspectionRecord $record) use ($duplicates, &$seen): void {
                $hash = $duplicates->generateHash(
                    (string) $record->licence_plate,
                    $record->inspection_date?->format('Y-m-d'),
                    $record->expiration_date->format('Y-m-d'),
                    (string) $record->normalized_phone_number,
                    (string) $record->status,
                );

                if (isset($seen[$hash])) {
                    // Same inspection already kept under another center/import — remove the later copy.
                    $record->delete();

                    return;
                }

                $seen[$hash] = $record->id;

                if ($record->record_hash !== $hash) {
                    $record->forceFill(['record_hash' => $hash])->saveQuietly();
                }
            });

        Schema::table('inspection_records', function (Blueprint $table) {
            $table->unique('record_hash');
        });
    }

    public function down(): void
    {
        // Irreversible: center_id was removed from the hash formula and cross-center duplicates may have been deleted.
    }
};
