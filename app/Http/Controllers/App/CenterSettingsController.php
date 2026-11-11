<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateNotificationSchedulesJob;
use App\Services\Audit\AuditLogService;
use App\Support\CurrentCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CenterSettingsController extends Controller
{
    public function edit(CurrentCenter $currentCenter): View
    {
        $center = $currentCenter->center();
        $dayOptions = config('notifications.reminder_day_options');

        return view('app.center.settings', compact('center', 'dayOptions'));
    }

    public function update(Request $request, CurrentCenter $currentCenter): RedirectResponse
    {
        $center = $currentCenter->center();

        if (filled($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('inspection_centers', 'code')->ignore($center->id)],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notification_language' => ['required', Rule::in(['fr', 'en'])],
        ]);

        $validated['code'] = filled($validated['code'] ?? null) ? $validated['code'] : null;

        $center->update($validated);

        app(AuditLogService::class)->record(
            'center_profile_updated',
            'Updated center profile for '.$center->name,
            centerId: $center->id,
        );

        return redirect()->route('center.settings.edit')
            ->with('status', __('Center profile updated.'));
    }

    public function updateReminders(Request $request, CurrentCenter $currentCenter): RedirectResponse
    {
        $center = $currentCenter->center();

        $validated = $request->validate([
            'sms_enabled' => ['boolean'],
            'whatsapp_enabled' => ['boolean'],
            'reminder_days' => ['required', 'array', 'min:1'],
            'reminder_days.*' => ['integer', Rule::in(config('notifications.reminder_day_options'))],
        ], [
            'reminder_days.required' => __('Choose at least one reminder day.'),
            'reminder_days.min' => __('Choose at least one reminder day.'),
        ]);

        $days = array_map('intval', $validated['reminder_days']);
        rsort($days);

        $center->update([
            'sms_enabled' => $request->boolean('sms_enabled'),
            'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
            'reminder_days' => array_values(array_unique($days)),
        ]);

        app(AuditLogService::class)->record(
            'center_reminders_updated',
            'Updated reminder settings (sms='.($center->sms_enabled ? 'on' : 'off')
                .', whatsapp='.($center->whatsapp_enabled ? 'on' : 'off')
                .', days='.implode(',', $center->reminder_days ?? []).')',
            centerId: $center->id,
        );

        if ($center->wasChanged(['sms_enabled', 'whatsapp_enabled', 'reminder_days'])) {
            GenerateNotificationSchedulesJob::dispatch($center->id);

            return redirect()->route('center.settings.edit')
                ->with('status', __('Reminder settings saved. Upcoming reminders are being updated.'));
        }

        return redirect()->route('center.settings.edit')
            ->with('status', __('Reminder settings saved.'));
    }
}
