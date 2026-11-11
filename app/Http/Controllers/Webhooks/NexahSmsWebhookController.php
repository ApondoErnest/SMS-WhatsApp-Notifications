<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSmsDeliveryReportJob;
use App\Services\Sms\NexahSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NexahSmsWebhookController extends Controller
{
    public function handle(Request $request, NexahSmsService $sms): JsonResponse
    {
        if (! $this->tokenIsValid($request)) {
            Log::warning('Nexah SMS webhook token rejected', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Forbidden'], 403);
        }

        $payload = $request->all();

        ProcessSmsDeliveryReportJob::dispatch($payload);

        return response()->json($sms->acknowledgeDeliveryReports($payload));
    }

    private function tokenIsValid(Request $request): bool
    {
        $expected = (string) config('services.nexah.webhook_token', '');

        // Allow local/dev when no shared token is configured yet.
        if ($expected === '') {
            return true;
        }

        $provided = (string) (
            $request->header('X-Nexah-Token')
            ?: $request->header('X-Webhook-Token')
            ?: $request->query('token', '')
        );

        return $provided !== '' && hash_equals($expected, $provided);
    }
}
