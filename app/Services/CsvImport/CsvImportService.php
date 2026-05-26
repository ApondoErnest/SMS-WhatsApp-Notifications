<?php

namespace App\Services\CsvImport;

use App\Models\ImportBatch;
use App\Models\User;
use App\Jobs\ProcessCsvImportJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CsvImportService
{
    public function upload(UploadedFile $file, User $user, int $centerId): ImportBatch
    {
        $storedName = Str::uuid().'.csv';
        $path = $file->storeAs('imports', $storedName);

        $batch = ImportBatch::create([
            'center_id' => $centerId,
            'uploaded_by' => $user->id,
            'filename' => $storedName,
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'pending',
        ]);

        ProcessCsvImportJob::dispatch($batch->id);

        return $batch;
    }
}
