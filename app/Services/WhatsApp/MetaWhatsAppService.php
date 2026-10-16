<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Direct integration with Meta WhatsApp Business Cloud API (no Twilio).
 *
 * Docs: https://developers.facebook.com/docs/whatsapp/cloud-api
 */
class MetaWhatsAppService
{
    public function isConfigured(): bool
    {
        return filled(config('services.whatsapp.token'))
            && filled(config('services.whatsapp.phone_number_id'));
    }

    /**
     * Send an approved template message.
     *
     * @param  list<string|array{name?: string, text: string}>  $bodyParameters
     *        Named params preferred: [['name' => 'customer_name', 'text' => 'Jean'], ...]
     *        Positional strings still accepted for older templates.
     * @return array{provider_message_id: string, response: array}
     */
    public function sendTemplate(
        string $toE164,
        string $templateName,
        string $languageCode,
        array $bodyParameters = [],
    ): array {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Meta WhatsApp Cloud API is not configured.');
        }

        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $version = config('services.whatsapp.api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        $components = [];
        if ($bodyParameters !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(function ($parameter) {
                    if (is_array($parameter)) {
                        $payload = [
                            'type' => 'text',
                            'text' => $this->sanitizeParameter((string) ($parameter['text'] ?? '')),
                        ];

                        if (filled($parameter['name'] ?? null)) {
                            $payload['parameter_name'] = (string) $parameter['name'];
                        }

                        return $payload;
                    }

                    return [
                        'type' => 'text',
                        'text' => $this->sanitizeParameter((string) $parameter),
                    ];
                }, $bodyParameters),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => ltrim($toE164, '+'),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        try {
            $response = Http::withToken(config('services.whatsapp.token'))
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($url, $payload)
                ->throw();
        } catch (RequestException $e) {
            $body = $e->response?->json() ?? [];
            $message = data_get($body, 'error.message')
                ?? $e->getMessage();
            $code = data_get($body, 'error.code');
            $subcode = data_get($body, 'error.error_subcode');

            Log::warning('Meta WhatsApp send failed', [
                'to' => $toE164,
                'template' => $templateName,
                'error' => $message,
                'code' => $code,
                'subcode' => $subcode,
            ]);

            throw new MetaWhatsAppException($message, (int) ($code ?? 0), $subcode ? (int) $subcode : null, $e);
        }

        $json = $response->json();
        $messageId = data_get($json, 'messages.0.id');

        if (! filled($messageId)) {
            throw new MetaWhatsAppException('Meta API response did not include a message id.');
        }

        return [
            'provider_message_id' => (string) $messageId,
            'response' => $json,
        ];
    }

    public function verifyWebhookChallenge(string $mode, string $token, string $challenge): ?string
    {
        $verifyToken = (string) config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && filled($verifyToken) && hash_equals($verifyToken, $token)) {
            return $challenge;
        }

        return null;
    }

    /**
     * @return list<array{
     *     provider_message_id: ?string,
     *     status: ?string,
     *     timestamp: ?int,
     *     recipient_id: ?string,
     *     error_message: ?string,
     *     error_code: ?int,
     *     pricing: ?array,
     *     inbound_text: ?string,
     *     inbound_from: ?string
     * }>
     */
    public function parseWebhookPayload(array $payload): array
    {
        $events = [];

        foreach (data_get($payload, 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                $value = data_get($change, 'value', []);

                foreach (data_get($value, 'statuses', []) as $status) {
                    $errors = data_get($status, 'errors', []);
                    $firstError = $errors[0] ?? null;

                    $events[] = [
                        'provider_message_id' => data_get($status, 'id'),
                        'status' => data_get($status, 'status'),
                        'timestamp' => isset($status['timestamp']) ? (int) $status['timestamp'] : null,
                        'recipient_id' => data_get($status, 'recipient_id'),
                        'error_message' => data_get($firstError, 'message') ?? data_get($firstError, 'title'),
                        'error_code' => isset($firstError['code']) ? (int) $firstError['code'] : null,
                        'pricing' => data_get($status, 'pricing'),
                        'inbound_text' => null,
                        'inbound_from' => null,
                    ];
                }

                foreach (data_get($value, 'messages', []) as $message) {
                    $events[] = [
                        'provider_message_id' => data_get($message, 'id'),
                        'status' => null,
                        'timestamp' => isset($message['timestamp']) ? (int) $message['timestamp'] : null,
                        'recipient_id' => null,
                        'error_message' => null,
                        'error_code' => null,
                        'pricing' => null,
                        'inbound_text' => data_get($message, 'text.body')
                            ?? data_get($message, 'button.text')
                            ?? data_get($message, 'interactive.button_reply.title'),
                        'inbound_from' => data_get($message, 'from'),
                    ];
                }
            }
        }

        return $events;
    }

    private function sanitizeParameter(string $text): string
    {
        // Meta rejects newlines and more than 4 consecutive spaces in template params.
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/ {5,}/', '    ', $text) ?? $text;

        return mb_substr(trim($text), 0, 1024);
    }
}
