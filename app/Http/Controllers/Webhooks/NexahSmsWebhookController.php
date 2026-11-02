<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSmsDeliveryReportJob;
use App\Services\Sms\NexahSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NexahSmsWebhookController extends Controller
{
    public function handle(Request $request, NexahSmsService $sms): JsonResponse
    {
        $payload = $request->all();

        ProcessSmsDeliveryReportJob::dispatch($payload);

        return response()->json($sms->acknowledgeDeliveryReports($payload));
    }
}
