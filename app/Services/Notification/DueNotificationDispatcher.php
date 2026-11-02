<?php

namespace App\Services\Notification;

use App\Jobs\SendSmsNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\NotificationSchedule;
use App\Services\Sms\NexahSmsService;
use App\Services\WhatsApp\MetaWhatsAppService;
use Carbon\Carbon;

class DueNotificationDispatcher
{
    public function __construct(
        private MetaWhatsAppService $whatsApp,
        private NexahSmsService $sms,
    ) {
    }

    public function markMissed(): int
    {
        return NotificationSchedule::query()
            ->where('status', NotificationSchedule::STATUS_PENDING)
            ->whereDate('scheduled_date', '<', Carbon::today())
            ->update(['status' => NotificationSchedule::STATUS_MISSED]);
    }

    public function countDueToday(): int
    {
        return $this->dueTodayQuery()->count();
    }

    public function withinSendWindow(?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        $start = Carbon::parse(config('notifications.send_window_start', '08:00'), $now->getTimezone())->setDateFrom($now);
        $end = Carbon::parse(config('notifications.send_window_end', '19:00'), $now->getTimezone())->setDateFrom($now);

        return $now->betweenIncluded($start, $end);
    }

    public function dispatchDueToday(): int
    {
        $dispatched = 0;
        $smsReady = (bool) config('notifications.sms.sending_enabled') && $this->sms->isConfigured();
        $whatsAppReady = $this->whatsApp->isConfigured();

        $this->dueTodayQuery()
            ->select('notification_schedules.*')
            ->chunkById(200, function ($schedules) use (&$dispatched, $smsReady, $whatsAppReady) {
                foreach ($schedules as $schedule) {
                    if ($schedule->channel === 'sms' && ! $smsReady) {
                        continue;
                    }

                    if ($schedule->channel === 'whatsapp' && ! $whatsAppReady) {
                        continue;
                    }

                    $claimed = NotificationSchedule::query()
                        ->whereKey($schedule->id)
                        ->where('status', NotificationSchedule::STATUS_PENDING)
                        ->update(['status' => NotificationSchedule::STATUS_QUEUED]);

                    if ($claimed === 0) {
                        continue;
                    }

                    match ($schedule->channel) {
                        'sms' => SendSmsNotificationJob::dispatch($schedule->id),
                        'whatsapp' => SendWhatsAppNotificationJob::dispatch($schedule->id),
                        default => NotificationSchedule::query()->whereKey($schedule->id)->update([
                            'status' => NotificationSchedule::STATUS_PENDING,
                        ]),
                    };

                    $dispatched++;
                }
            }, 'notification_schedules.id', 'id');

        return $dispatched;
    }

    private function dueTodayQuery()
    {
        return NotificationSchedule::query()
            ->join('inspection_centers', 'inspection_centers.id', '=', 'notification_schedules.center_id')
            ->where('notification_schedules.status', NotificationSchedule::STATUS_PENDING)
            ->whereDate('notification_schedules.scheduled_date', Carbon::today())
            ->where('inspection_centers.status', 'active')
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->where('notification_schedules.channel', 'sms')->where('inspection_centers.sms_enabled', true);
                })->orWhere(function ($q) {
                    $q->where('notification_schedules.channel', 'whatsapp')->where('inspection_centers.whatsapp_enabled', true);
                });
            });
    }
}
