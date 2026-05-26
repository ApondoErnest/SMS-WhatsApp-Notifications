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

    public int $timeout = 300;

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

        $batch->update(['status' => 'processing']);

        $importService->process($batch);
    }

    public function failed(Throwable $exception): void
    {
        $batch = ImportBatch::find($this->importBatchId);
        $batch?->update(['status' => 'failed']);
    }
}
