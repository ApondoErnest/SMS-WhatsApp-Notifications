<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\NotificationSchedule;
use App\Services\Notification\MessagingConsentService;
use App\Services\WhatsApp\MetaWhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class ProcessMetaWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public array $payload)
    {
        $this->onQueue('webhooks');
    }

    public function handle(MetaWhatsAppService $whatsApp, MessagingConsentService $consent): void
    {
        foreach ($whatsApp->parseWebhookPayload($this->payload) as $event) {
            if (filled($event['inbound_from']) && filled($event['inbound_text'])) {
                $this->handleInboundOptOut($event, $consent);

                continue;
            }

            if (! filled($event['provider_message_id']) || ! filled($event['status'])) {
                continue;
            }

            $log = NotificationLog::query()
                ->where('provider', 'meta_whatsapp')
                ->where('provider_message_id', $event['provider_message_id'])
                ->first();

            if (! $log) {
                continue;
            }

            $this->applyStatus($log, $event);
        }
    }

    /**
     * @param  array{inbound_from: ?string, inbound_text: ?string}  $event
     */
    private function handleInboundOptOut(array $event, MessagingConsentService $consent): void
    {
        $text = mb_strtolower(trim((string) $event['inbound_text']));
        $optOutKeywords = ['stop', 'arrêt', 'arret', 'unsubscribe', 'désabonner', 'desabonner', 'optout', 'opt out'];

        $isOptOut = false;
        foreach ($optOutKeywords as $keyword) {
            if ($text === $keyword || str_contains($text, $keyword)) {
                $isOptOut = true;
                break;
            }
        }

        if (! $isOptOut) {
            return;
        }

        $from = (string) $event['inbound_from'];
        $phone = str_starts_with($from, '+') ? $from : '+'.$from;

        NotificationLog::query()
            ->where('provider', 'meta_whatsapp')
            ->where('phone_number', $phone)
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->pluck('center_id')
            ->unique()
            ->each(function (int $centerId) use ($consent, $phone, $event) {
                $consent->revoke($centerId, $phone, 'whatsapp', 'whatsapp_inbound', $event['inbound_text']);
            });
    }

    /**
     * @param  array{status: ?string, timestamp: ?int, error_message: ?string, pricing: ?array}  $event
     */
    private function applyStatus(NotificationLog $log, array $event): void
    {
        $status = (string) $event['status'];
        $at = isset($event['timestamp'])
            ? Carbon::createFromTimestamp($event['timestamp'])
            : now();

        $updates = match ($status) {
            'sent' => [
                'delivery_status' => $this->preferStatus($log->delivery_status, 'sent'),
                'sent_at' => $log->sent_at ?? $at,
            ],
            'delivered' => [
                'delivery_status' => $this->preferStatus($log->delivery_status, 'delivered'),
                'delivered_at' => $log->delivered_at ?? $at,
            ],
            'read' => [
                'delivery_status' => 'read',
                'read_at' => $log->read_at ?? $at,
                'delivered_at' => $log->delivered_at ?? $at,
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

        if (is_array($event['pricing'] ?? null)) {
            $category = strtolower((string) data_get($event['pricing'], 'category', 'utility'));
            $billable = (bool) data_get($event['pricing'], 'billable', false);
            $rates = config('notifications.whatsapp.estimated_rates_usd', []);

            $updates['billable'] = $billable;
            $updates['pricing_model'] = data_get($event['pricing'], 'pricing_model');
            $updates['conversation_category'] = $category;
            $updates['cost_currency'] = 'USD';
            $updates['estimated_cost'] = $billable ? (float) ($rates[$category] ?? 0) : 0;
        }

        $log->update($updates);

        if ($status === 'failed' && $log->schedule) {
            $log->schedule->update(['status' => NotificationSchedule::STATUS_FAILED]);
        }
    }

    private function preferStatus(string $current, string $incoming): string
    {
        $rank = ['pending' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 4, 'skipped' => 0];

        return ($rank[$incoming] ?? 0) >= ($rank[$current] ?? 0) ? $incoming : $current;
    }
}
