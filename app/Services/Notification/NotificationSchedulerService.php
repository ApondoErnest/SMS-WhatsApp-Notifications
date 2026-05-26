<?php

namespace App\Services\Notification;

use App\Models\InspectionRecord;
use App\Models\NotificationSchedule;
use Carbon\Carbon;

class NotificationSchedulerService
{
    public function generateForRecord(InspectionRecord $record): int
    {
        $reminderDays = config('import.default_reminder_days', [30, 14, 7, 1]);
        $created = 0;

        foreach ($reminderDays as $daysBefore) {
            $scheduledDate = Carbon::parse($record->expiration_date)->subDays($daysBefore);

            if ($scheduledDate->isPast()) {
                continue;
            }

            foreach (['sms', 'whatsapp'] as $channel) {
                $schedule = NotificationSchedule::firstOrCreate(
                    [
                        'inspection_record_id' => $record->id,
                        'channel' => $channel,
                        'scheduled_date' => $scheduledDate->toDateString(),
                    ],
                    [
                        'center_id' => $record->center_id,
                        'status' => 'pending',
                    ]
                );

                if ($schedule->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        return $created;
    }

    /**
     * @param  iterable<InspectionRecord>  $records
     */
    public function generateForMany(iterable $records): int
    {
        $total = 0;
        foreach ($records as $record) {
            $total += $this->generateForRecord($record);
        }

        return $total;
    }
}
