<?php

namespace App\Jobs;

use App\Models\InspectionCenter;
use App\Services\Notification\NotificationSchedulerService;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateNotificationSchedulesJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $centerId)
    {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return (string) $this->centerId;
    }

    public function handle(NotificationSchedulerService $scheduler): void
    {
        $center = InspectionCenter::find($this->centerId);

        if ($center) {
            $scheduler->rebuildForCenter($center);
        }
    }
}
