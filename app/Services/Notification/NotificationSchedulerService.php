<?php

namespace App\Services\Notification;

use App\Models\InspectionCenter;
use App\Models\InspectionRecord;
use App\Models\NotificationSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class NotificationSchedulerService
{
    public function generateForRecord(InspectionRecord $record): int
    {
        $center = $record->center;

        if (! $center || $record->isRefused() || $this->isSuperseded($record)) {
            return 0;
        }

        $this->cancelSupersededBy($record);

        $channels = $center->enabledChannels();
        $today = Carbon::today();
        $created = 0;

        foreach ($center->reminderDays() as $daysBefore) {
            $scheduledDate = Carbon::parse($record->expiration_date)->startOfDay()->subDays($daysBefore);

            if ($scheduledDate->lt($today)) {
                continue;
            }

            foreach ($channels as $channel) {
                $schedule = NotificationSchedule::firstOrCreate(
                    [
                        'inspection_record_id' => $record->id,
                        'channel' => $channel,
                        'scheduled_date' => $scheduledDate,
                    ],
                    [
                        'center_id' => $record->center_id,
                        'days_before' => $daysBefore,
                        'status' => NotificationSchedule::STATUS_PENDING,
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

    /**
     * Drops upcoming pending reminders for the center and recreates them from its current settings.
     *
     * @return array{removed: int, created: int}
     */
    public function rebuildForCenter(InspectionCenter $center): array
    {
        $removed = NotificationSchedule::query()
            ->where('center_id', $center->id)
            ->where('status', NotificationSchedule::STATUS_PENDING)
            ->whereDate('scheduled_date', '>=', Carbon::today())
            ->delete();

        $created = 0;

        InspectionRecord::query()
            ->where('center_id', $center->id)
            ->whereDate('expiration_date', '>=', Carbon::today())
            ->orderBy('id')
            ->chunkById(500, function ($records) use ($center, &$created) {
                foreach ($records as $record) {
                    $record->setRelation('center', $center);
                    $created += $this->generateForRecord($record);
                }
            });

        return ['removed' => $removed, 'created' => $created];
    }

    private function isSuperseded(InspectionRecord $record): bool
    {
        return $this->samePlateQuery($record)
            ->whereDate('expiration_date', '>', $record->expiration_date)
            ->get()
            ->contains(fn (InspectionRecord $other) => ! $other->isRefused());
    }

    private function cancelSupersededBy(InspectionRecord $record): void
    {
        $olderIds = $this->samePlateQuery($record)
            ->whereDate('expiration_date', '<', $record->expiration_date)
            ->pluck('id');

        if ($olderIds->isEmpty()) {
            return;
        }

        NotificationSchedule::query()
            ->whereIn('inspection_record_id', $olderIds)
            ->where('status', NotificationSchedule::STATUS_PENDING)
            ->update(['status' => NotificationSchedule::STATUS_CANCELLED]);
    }

    private function samePlateQuery(InspectionRecord $record): Builder
    {
        $plate = self::normalizePlate($record->licence_plate);

        return InspectionRecord::query()
            ->where('center_id', $record->center_id)
            ->where('id', '!=', $record->id)
            ->whereRaw("UPPER(REPLACE(REPLACE(licence_plate, ' ', ''), '-', '')) = ?", [$plate]);
    }

    public static function normalizePlate(?string $plate): string
    {
        return mb_strtoupper(str_replace([' ', '-'], '', (string) $plate));
    }
}
