<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateNotificationSchedulesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $importBatchId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        // TODO: Phase 5 — generate reminder schedules for all records in the batch
    }
}
