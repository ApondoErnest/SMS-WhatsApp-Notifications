<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InspectionRecord;
use App\Models\MessagingConsent;
use App\Services\Notification\MessagingConsentService;
use App\Support\CurrentCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecordController extends Controller
{
    public function show(InspectionRecord $record): View
    {
        $centerId = app(CurrentCenter::class)->id();
        abort_if($record->center_id !== $centerId, 403);

        $record->load(['importBatch', 'notificationSchedules', 'notificationLogs']);

        $whatsappConsent = MessagingConsent::query()
            ->where('center_id', $record->center_id)
            ->where('phone_number', $record->normalized_phone_number)
            ->where('channel', 'whatsapp')
            ->first();

        return view('app.records.show', compact('record', 'whatsappConsent'));
    }

    public function updateWhatsAppConsent(
        Request $request,
        InspectionRecord $record,
        MessagingConsentService $consentService,
    ): RedirectResponse {
        $centerId = app(CurrentCenter::class)->id();
        abort_if($record->center_id !== $centerId, 403);

        $validated = $request->validate([
            'consented' => ['required', 'boolean'],
        ]);

        if ($validated['consented']) {
            $consentService->grant($record->center_id, $record->normalized_phone_number, 'whatsapp', 'admin');
            $message = __('WhatsApp consent granted for this phone number.');
        } else {
            $consentService->revoke($record->center_id, $record->normalized_phone_number, 'whatsapp', 'admin');
            $message = __('WhatsApp opt-out recorded for this phone number.');
        }

        return redirect()->route('records.show', $record)->with('status', $message);
    }
}
