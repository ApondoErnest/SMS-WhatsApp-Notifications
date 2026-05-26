<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $notificationScheduleId)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        // TODO: Phase 6 — send SMS via AfricaTalkingSmsService, log result
    }
}
