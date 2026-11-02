<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\NotificationSchedule;
use App\Services\Sms\NexahSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class ProcessSmsDeliveryReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public array $payload)
    {
        $this->onQueue('webhooks');
    }

    public function handle(NexahSmsService $sms): void
    {
        foreach ($sms->parseDeliveryReports($this->payload) as $event) {
            if (! filled($event['provider_message_id']) || ! filled($event['status'])) {
                continue;
            }

            $log = NotificationLog::query()
                ->where('provider', 'nexah')
                ->where('provider_message_id', $event['provider_message_id'])
                ->first();

            if (! $log) {
                continue;
            }

            $this->applyStatus($log, $event);
        }
    }

    /**
     * @param  array{status: ?string, delivered_at: ?string, error_message: ?string}  $event
     */
    private function applyStatus(NotificationLog $log, array $event): void
    {
        $status = (string) $event['status'];
        $at = filled($event['delivered_at'] ?? null)
            ? Carbon::parse((string) $event['delivered_at'])
            : now();

        $updates = match ($status) {
            'delivered' => [
                'delivery_status' => $this->preferStatus($log->delivery_status, 'delivered'),
                'delivered_at' => $log->delivered_at ?? $at,
                'error_message' => null,
            ],
            'failed' => [
                'delivery_status' => 'failed',
                'failed_at' => $log->failed_at ?? $at,
                'error_message' => $event['error_message'] ?? $log->error_message,
            ],
            default => [
                'delivery_status' => $status,
            ],
        };

        $log->update($updates);

        if ($status === 'failed' && $log->schedule) {
            $log->schedule->update(['status' => NotificationSchedule::STATUS_FAILED]);
        }
    }

    private function preferStatus(string $current, string $incoming): string
    {
        $rank = ['pending' => 0, 'sent' => 1, 'delivered' => 2, 'failed' => 3, 'skipped' => 0];

        return ($rank[$incoming] ?? 0) >= ($rank[$current] ?? 0) ? $incoming : $current;
    }
}
