<?php

namespace App\Console\Commands;

use App\Services\Notification\DueNotificationDispatcher;
use Illuminate\Console\Command;

class DispatchDueNotifications extends Command
{
    protected $signature = 'notifications:dispatch-due';

    protected $description = 'Mark past reminders as missed and queue the reminders due today for sending';

    public function handle(DueNotificationDispatcher $dispatcher): int
    {
        $missed = $dispatcher->markMissed();
        $due = $dispatcher->countDueToday();

        $this->info("Marked {$missed} past reminder(s) as missed. {$due} reminder(s) due today.");

        if (! config('notifications.sending_enabled')) {
            $this->line('Sending is disabled (NOTIFICATIONS_SENDING_ENABLED=false); due reminders stay pending.');

            return self::SUCCESS;
        }

        if (! $dispatcher->withinSendWindow()) {
            $this->line('Outside the send window; due reminders will be queued later today.');

            return self::SUCCESS;
        }

        $queued = $dispatcher->dispatchDueToday();
        $this->info("Queued {$queued} reminder(s) for sending.");

        return self::SUCCESS;
    }
}
