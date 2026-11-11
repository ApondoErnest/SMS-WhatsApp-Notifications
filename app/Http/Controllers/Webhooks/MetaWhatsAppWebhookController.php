<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMetaWebhookJob;
use App\Services\WhatsApp\MetaWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MetaWhatsAppWebhookController extends Controller
{
    public function verify(Request $request, MetaWhatsAppService $whatsApp): Response
    {
        $challenge = $whatsApp->verifyWebhookChallenge(
            (string) $request->query('hub_mode', $request->query('hub.mode', '')),
            (string) $request->query('hub_verify_token', $request->query('hub.verify_token', '')),
            (string) $request->query('hub_challenge', $request->query('hub.challenge', '')),
        );

        if ($challenge === null) {
            Log::warning('Meta WhatsApp webhook verification failed');

            return response('Forbidden', 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function handle(Request $request): Response
    {
        if (! $this->signatureIsValid($request)) {
            Log::warning('Meta WhatsApp webhook signature rejected', [
                'ip' => $request->ip(),
            ]);

            return response('Forbidden', 403);
        }

        ProcessMetaWebhookJob::dispatch($request->all());

        return response('EVENT_RECEIVED', 200);
    }

    private function signatureIsValid(Request $request): bool
    {
        $appSecret = (string) config('services.whatsapp.app_secret', '');

        // Allow local/dev when the app secret is not configured yet.
        if ($appSecret === '') {
            return true;
        }

        $header = (string) $request->header('X-Hub-Signature-256', '');
        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $header);
    }
}
