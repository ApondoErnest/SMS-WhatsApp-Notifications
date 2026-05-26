<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessCsvImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $importBatchId)
    {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        // TODO: Phase 2 — read CSV, validate, normalize, save records, track failures
    }
}
