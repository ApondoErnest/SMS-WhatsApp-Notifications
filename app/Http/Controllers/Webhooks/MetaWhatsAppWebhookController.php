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
        ProcessMetaWebhookJob::dispatch($request->all());

        return response('EVENT_RECEIVED', 200);
    }
}
