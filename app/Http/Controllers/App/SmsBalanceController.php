<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Sms\NexahSmsException;
use App\Services\Sms\NexahSmsService;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

class SmsBalanceController extends Controller
{
    /**
     * JSON endpoint used by the Centers page refresh button.
     */
    public function __invoke(NexahSmsService $sms): JsonResponse
    {
        if (! $sms->isConfigured()) {
            return response()->json([
                'ok' => false,
                'message' => __('Nexah SMS is not configured. Set NEXAH_SMS_USER, NEXAH_SMS_PASSWORD, and NEXAH_SMS_SENDER_ID.'),
            ], 422);
        }

        try {
            $balance = $sms->getBalance();
        } catch (NexahSmsException|RuntimeException|Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'credit' => $balance['credit'],
            'account_expires_at' => $balance['account_expires_at'],
            'balance_expires_at' => $balance['balance_expires_at'],
        ]);
    }
}
