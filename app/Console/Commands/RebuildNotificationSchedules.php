<?php

namespace App\Console\Commands;

use App\Models\InspectionCenter;
use App\Services\Notification\NotificationSchedulerService;
use Illuminate\Console\Command;

class RebuildNotificationSchedules extends Command
{
    protected $signature = 'notifications:rebuild-schedules {--center= : Only rebuild this center ID}';

    protected $description = 'Recreate upcoming pending reminders from each center\'s current reminder settings';

    public function handle(NotificationSchedulerService $scheduler): int
    {
        $centers = InspectionCenter::query()
            ->when($this->option('center'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('id')
            ->get();

        if ($centers->isEmpty()) {
            $this->error('No matching center found.');

            return self::FAILURE;
        }

        foreach ($centers as $center) {
            $result = $scheduler->rebuildForCenter($center);
            $this->info("{$center->name}: removed {$result['removed']}, created {$result['created']} reminder(s).");
        }

        return self::SUCCESS;
    }
}
