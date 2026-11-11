<?php

namespace App\Services\Sms;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Nexah Bulk SMS API (https://nexah.net/).
 *
 * Docs: NEXAH SMS-API Interfaces — POST /sendsms, /smscredit, partner DLR webhook.
 */
class NexahSmsService
{
    public function isConfigured(): bool
    {
        return filled(config('services.nexah.user'))
            && filled(config('services.nexah.password'))
            && filled(config('services.nexah.sender_id'));
    }

    /**
     * Send one SMS. Returns the provider message id from the first recipient result.
     *
     * @return array{provider_message_id: string, response: array}
     */
    public function send(string $toE164, string $message): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Nexah SMS API is not configured.');
        }

        $mobile = $this->formatMobile($toE164);
        $payload = [
            'user' => config('services.nexah.user'),
            'password' => config('services.nexah.password'),
            'senderid' => config('services.nexah.sender_id'),
            'sms' => $message,
            'mobiles' => $mobile,
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($this->endpoint('sendsms'), $payload)
                ->throw();
        } catch (RequestException $e) {
            Log::warning('Nexah SMS HTTP send failed', [
                'to' => $mobile,
                'error' => $e->getMessage(),
                'body' => $e->response?->json(),
            ]);

            throw new NexahSmsException(
                $e->response?->json('responsemessage')
                    ?? $e->response?->json('responsedescription')
                    ?? $e->getMessage(),
                $e->response?->json('responsecode'),
                $e,
            );
        }

        $json = $response->json() ?? [];
        $responseCode = (int) ($json['responsecode'] ?? 0);

        if ($responseCode !== 1) {
            $error = $this->firstRecipientError($json)
                ?? ($json['responsemessage'] ?? $json['responsedescription'] ?? 'Nexah SMS send failed.');

            throw new NexahSmsException(
                (string) $error['message'],
                $error['code'] ?? $responseCode,
            );
        }

        $first = data_get($json, 'sms.0', []);
        $recipientStatus = strtolower((string) ($first['status'] ?? 'success'));

        if ($recipientStatus !== 'success') {
            throw new NexahSmsException(
                (string) ($first['errordescription'] ?: $first['errorcode'] ?: 'Nexah rejected the recipient.'),
                $first['errorcode'] ?? null,
            );
        }

        $messageId = trim((string) ($first['messageid'] ?? ''));

        if ($messageId === '') {
            throw new NexahSmsException('Nexah API response did not include a message id.');
        }

        return [
            'provider_message_id' => $messageId,
            'response' => $json,
        ];
    }

    /**
     * @return array{credit: int, account_expires_at: ?string, balance_expires_at: ?string, raw: array}
     */
    public function getBalance(bool $fresh = false): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Nexah SMS API is not configured.');
        }

        $cacheKey = 'nexah.sms.balance.'.sha1((string) config('services.nexah.user'));

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addSeconds(90), function (): array {
            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->timeout(8)
                    ->connectTimeout(3)
                    ->post($this->endpoint('smscredit'), [
                        'user' => config('services.nexah.user'),
                        'password' => config('services.nexah.password'),
                    ])
                    ->throw();
            } catch (RequestException $e) {
                Log::warning('Nexah SMS balance check failed', [
                    'error' => $e->getMessage(),
                    'body' => $e->response?->json(),
                ]);

                throw new NexahSmsException(
                    $e->response?->json('responsemessage')
                        ?? $e->response?->json('responsedescription')
                        ?? $e->getMessage(),
                    previous: $e,
                );
            }

            $json = $response->json() ?? [];

            if (! array_key_exists('credit', $json)) {
                throw new NexahSmsException(
                    (string) ($json['responsemessage'] ?? $json['responsedescription'] ?? 'Nexah balance response was invalid.'),
                );
            }

            return [
                'credit' => (int) $json['credit'],
                'account_expires_at' => $json['accountexpdate'] ?? null,
                'balance_expires_at' => $json['balanceexpdate'] ?? null,
                'raw' => $json,
            ];
        });
    }

    /**
     * Normalize Nexah DLR payload into status events.
     *
     * @return list<array{
     *     provider_message_id: ?string,
     *     status: ?string,
     *     phone_number: ?string,
     *     delivered_at: ?string,
     *     error_message: ?string
     * }>
     */
    public function parseDeliveryReports(array $payload): array
    {
        $events = [];

        foreach ($payload['dlrlist'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $rawStatus = $item['status'] ?? $item['Status'] ?? null;
            $normalized = $this->normalizeDlrStatus($rawStatus, $item);

            $events[] = [
                'provider_message_id' => isset($item['messageid']) ? trim((string) $item['messageid']) : null,
                'status' => $normalized['status'],
                'phone_number' => isset($item['mobileno']) ? (string) $item['mobileno'] : null,
                'delivered_at' => $item['deliverytime'] ?? $item['senttime'] ?? null,
                'error_message' => $normalized['error_message'],
            ];
        }

        return $events;
    }

    /**
     * Build the acknowledgement payload Nexah expects (status 1 = accepted).
     *
     * @return array{dlrlist: list<array<string, mixed>>}
     */
    public function acknowledgeDeliveryReports(array $payload): array
    {
        $list = [];

        foreach ($payload['dlrlist'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $list[] = array_merge($item, ['status' => 1]);
        }

        return ['dlrlist' => $list];
    }

    public function formatMobile(string $toE164): string
    {
        return ltrim(trim($toE164), '+');
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.nexah.base_url'), '/').'/'.ltrim($path, '/');
    }

    /**
     * @return array{message: string, code: int|string|null}|null
     */
    private function firstRecipientError(array $json): ?array
    {
        $first = data_get($json, 'sms.0');

        if (! is_array($first)) {
            return null;
        }

        $message = $first['errordescription']
            ?: $json['responsemessage']
            ?: $json['responsedescription']
            ?: 'Nexah SMS send failed.';

        return [
            'message' => (string) $message,
            'code' => $first['errorcode'] ?? $json['responsecode'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{status: ?string, error_message: ?string}
     */
    private function normalizeDlrStatus(mixed $rawStatus, array $item): array
    {
        if (is_string($rawStatus)) {
            $upper = strtoupper(trim($rawStatus));

            if (in_array($upper, ['DELIVRD', 'DELIVERED', '1'], true)) {
                return ['status' => 'delivered', 'error_message' => null];
            }

            if (in_array($upper, ['UNDELIV', 'UNDELIVERED', 'FAILED', '0'], true)) {
                return [
                    'status' => 'failed',
                    'error_message' => (string) ($item['reponsedescription'] ?? $item['Reponsedescription'] ?? 'SMS not delivered'),
                ];
            }
        }

        if ($rawStatus === 1 || $rawStatus === '1') {
            return ['status' => 'delivered', 'error_message' => null];
        }

        if ($rawStatus === 0 || $rawStatus === '0') {
            return [
                'status' => 'failed',
                'error_message' => (string) ($item['reponsedescription'] ?? $item['Reponsedescription'] ?? 'SMS not delivered'),
            ];
        }

        return ['status' => null, 'error_message' => null];
    }
}
