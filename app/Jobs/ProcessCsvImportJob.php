<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\CsvImport\CsvImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessCsvImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /** Large CSV imports can run for a long time; Horizon import supervisor must exceed this. */
    public int $timeout = 3600;

    public int $memory = 512;

    public function __construct(public int $importBatchId)
    {
        $this->onQueue('imports');
    }

    public function handle(CsvImportService $importService): void
    {
        $batch = ImportBatch::find($this->importBatchId);

        if (! $batch) {
            return;
        }

        $batch->update([
            'status' => 'processing',
            'processed_rows' => 0,
            'error_message' => null,
        ]);

        $importService->process($batch);
    }

    public function failed(Throwable $exception): void
    {
        $batch = ImportBatch::find($this->importBatchId);
        $batch?->update([
            'status' => 'failed',
            'error_message' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
