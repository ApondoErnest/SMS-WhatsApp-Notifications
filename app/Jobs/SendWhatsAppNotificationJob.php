<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\NotificationSchedule;
use App\Services\Notification\MessagePersonalizer;
use App\Services\Notification\MessagingConsentService;
use App\Services\WhatsApp\MetaWhatsAppException;
use App\Services\WhatsApp\MetaWhatsAppService;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppNotificationJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $notificationScheduleId)
    {
        $this->onQueue('notifications');
    }

    public function uniqueId(): string
    {
        return (string) $this->notificationScheduleId;
    }

    public function handle(
        MetaWhatsAppService $whatsApp,
        MessagePersonalizer $personalizer,
        MessagingConsentService $consent,
    ): void {
        $schedule = NotificationSchedule::query()
            ->with(['inspectionRecord.center', 'center'])
            ->find($this->notificationScheduleId);

        if (! $schedule || $schedule->channel !== 'whatsapp') {
            return;
        }

        if (in_array($schedule->status, [
            NotificationSchedule::STATUS_SENT,
            NotificationSchedule::STATUS_CANCELLED,
            NotificationSchedule::STATUS_MISSED,
        ], true)) {
            return;
        }

        $record = $schedule->inspectionRecord;
        if (! $record) {
            $this->failSchedule($schedule, 'Inspection record missing.');

            return;
        }

        if ($record->isRefused()) {
            $schedule->update(['status' => NotificationSchedule::STATUS_CANCELLED]);

            return;
        }

        $center = $record->center ?? $schedule->center;
        if (! $center || $center->status !== 'active' || ! $center->whatsapp_enabled) {
            $this->failSchedule($schedule, 'WhatsApp is disabled for this center.');

            return;
        }

        if (! $whatsApp->isConfigured()) {
            // Leave pending so it can be retried once credentials are set.
            $schedule->update(['status' => NotificationSchedule::STATUS_PENDING]);

            return;
        }

        $phone = $record->normalized_phone_number;
        if (! filled($phone)) {
            $this->failSchedule($schedule, 'Missing normalized phone number.');

            return;
        }

        if (! $consent->isAllowed($record->center_id, $phone, 'whatsapp')) {
            $schedule->update(['status' => NotificationSchedule::STATUS_CANCELLED]);
            $this->writeLog($schedule, $phone, 'Consent missing or opted out.', 'skipped', '');

            return;
        }

        // Duplicate prevention: already sent for this schedule.
        if (NotificationLog::query()
            ->where('notification_schedule_id', $schedule->id)
            ->where('channel', 'whatsapp')
            ->whereIn('delivery_status', ['sent', 'delivered', 'read'])
            ->exists()) {
            $schedule->update(['status' => NotificationSchedule::STATUS_SENT]);

            return;
        }

        $template = $personalizer->resolveWhatsAppTemplate(
            $record->center_id,
            $center->notificationLanguage(),
        );
        $templateName = $template?->meta_template_name
            ?: config('notifications.whatsapp.default_template_name');
        $languageCode = $template?->meta_language_code
            ?: ($template?->language === 'en' ? 'en' : config('notifications.whatsapp.default_language_code', 'fr'));

        if (! filled($templateName)) {
            $this->failSchedule($schedule, 'No Meta WhatsApp template name configured.');

            return;
        }

        $parameters = $personalizer->metaBodyParameters($record);
        $preview = $template
            ? $personalizer->render($template->content, $record)
            : implode(' | ', array_column($parameters, 'text'));

        $schedule->update([
            'attempts' => $schedule->attempts + 1,
            'last_attempt_at' => now(),
            'status' => NotificationSchedule::STATUS_QUEUED,
        ]);

        try {
            $result = $whatsApp->sendTemplate($phone, $templateName, $languageCode, $parameters);

            NotificationLog::create([
                'center_id' => $record->center_id,
                'inspection_record_id' => $record->id,
                'notification_schedule_id' => $schedule->id,
                'channel' => 'whatsapp',
                'provider' => 'meta_whatsapp',
                'phone_number' => $phone,
                'message' => $preview,
                'provider_message_id' => $result['provider_message_id'],
                'delivery_status' => 'sent',
                'sent_at' => now(),
            ]);

            $schedule->update(['status' => NotificationSchedule::STATUS_SENT]);
        } catch (MetaWhatsAppException $e) {
            $this->writeLog($schedule, $phone, $e->getMessage(), 'failed', $preview);

            if ($e->isPermanent() || $this->attempts() >= $this->tries) {
                $schedule->update(['status' => NotificationSchedule::STATUS_FAILED]);

                return;
            }

            $schedule->update(['status' => NotificationSchedule::STATUS_PENDING]);
            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        $schedule = NotificationSchedule::find($this->notificationScheduleId);
        if ($schedule && $schedule->status !== NotificationSchedule::STATUS_SENT) {
            $schedule->update(['status' => NotificationSchedule::STATUS_FAILED]);
        }

        Log::error('SendWhatsAppNotificationJob failed permanently', [
            'schedule_id' => $this->notificationScheduleId,
            'error' => $e?->getMessage(),
        ]);
    }

    private function failSchedule(NotificationSchedule $schedule, string $reason): void
    {
        $phone = $schedule->inspectionRecord?->normalized_phone_number ?? '';
        $this->writeLog($schedule, $phone, $reason, 'failed', '');
        $schedule->update([
            'status' => NotificationSchedule::STATUS_FAILED,
            'attempts' => $schedule->attempts + 1,
            'last_attempt_at' => now(),
        ]);
    }

    private function writeLog(
        NotificationSchedule $schedule,
        string $phone,
        string $error,
        string $status,
        string $message,
    ): void {
        $record = $schedule->inspectionRecord;
        if (! $record) {
            return;
        }

        NotificationLog::create([
            'center_id' => $record->center_id,
            'inspection_record_id' => $record->id,
            'notification_schedule_id' => $schedule->id,
            'channel' => 'whatsapp',
            'provider' => 'meta_whatsapp',
            'phone_number' => $phone,
            'message' => $message,
            'delivery_status' => $status,
            'error_message' => $error,
            'failed_at' => $status === 'failed' ? now() : null,
        ]);
    }
}
