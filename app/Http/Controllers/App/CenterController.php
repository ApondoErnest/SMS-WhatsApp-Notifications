<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InspectionCenter;
use App\Models\User;
use App\Services\Sms\NexahSmsService;
use App\Support\CurrentCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CenterController extends Controller
{
    public function index(CurrentCenter $currentCenter, NexahSmsService $sms): View
    {
        $centers = InspectionCenter::query()
            ->withCount(['users', 'inspectionRecords', 'importBatches'])
            ->orderBy('name')
            ->get();

        return view('app.centers.index', [
            'centers' => $centers,
            'currentCenter' => $currentCenter->center(),
            'smsBalance' => $this->smsBalancePayload($sms),
        ]);
    }

    /**
     * @return array{ok: bool, credit: ?int, account_expires_at: ?string, balance_expires_at: ?string, message: ?string}
     */
    private function smsBalancePayload(NexahSmsService $sms): array
    {
        if (! $sms->isConfigured()) {
            return [
                'ok' => false,
                'credit' => null,
                'account_expires_at' => null,
                'balance_expires_at' => null,
                'message' => __('Nexah SMS is not configured. Set NEXAH_SMS_USER, NEXAH_SMS_PASSWORD, and NEXAH_SMS_SENDER_ID.'),
            ];
        }

        try {
            $balance = $sms->getBalance();

            return [
                'ok' => true,
                'credit' => $balance['credit'],
                'account_expires_at' => $balance['account_expires_at'],
                'balance_expires_at' => $balance['balance_expires_at'],
                'message' => null,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'credit' => null,
                'account_expires_at' => null,
                'balance_expires_at' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function create(): View
    {
        return view('app.centers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $center = InspectionCenter::create($this->validated($request));

        session(['current_center_id' => $center->id]);
        app(CurrentCenter::class)->forget();

        return redirect()->route('centers.index')
            ->with('status', __('Center created successfully.'));
    }

    public function edit(InspectionCenter $center): View
    {
        return view('app.centers.edit', compact('center'));
    }

    public function update(Request $request, InspectionCenter $center): RedirectResponse
    {
        $center->update($this->validated($request, $center));

        return redirect()->route('centers.index')
            ->with('status', __('Center updated successfully.'));
    }

    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'center_id' => ['required', 'exists:inspection_centers,id'],
        ]);

        session(['current_center_id' => (int) $validated['center_id']]);
        app(CurrentCenter::class)->forget();

        return redirect()->route('dashboard')->with('status', __('Center switched.'));
    }

    public function destroy(InspectionCenter $center): RedirectResponse
    {
        $this->deleteCenters(collect([$center]));

        return redirect()->route('centers.index')
            ->with('status', __('Center deleted successfully.'));
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'center_ids' => ['required', 'array', 'min:1'],
            'center_ids.*' => ['integer', 'distinct', 'exists:inspection_centers,id'],
        ]);

        $centers = InspectionCenter::query()
            ->whereIn('id', $validated['center_ids'])
            ->get();

        $deleted = $this->deleteCenters($centers);

        return redirect()->route('centers.index')
            ->with('status', trans_choice(':count center deleted.|:count centers deleted.', $deleted));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, InspectionCenter>  $centers
     */
    private function deleteCenters($centers): int
    {
        if ($centers->isEmpty()) {
            throw ValidationException::withMessages([
                'center_ids' => __('Select at least one center to delete.'),
            ]);
        }

        $remaining = InspectionCenter::query()->count() - $centers->count();

        if ($remaining < 1) {
            throw ValidationException::withMessages([
                'center_ids' => __('You cannot delete every center. Keep at least one.'),
            ]);
        }

        $ids = $centers->pluck('id')->all();
        $currentId = session('current_center_id');

        DB::transaction(function () use ($ids): void {
            User::query()->whereIn('center_id', $ids)->delete();
            InspectionCenter::query()->whereIn('id', $ids)->delete();
        });

        if ($currentId && in_array((int) $currentId, array_map('intval', $ids), true)) {
            $fallback = InspectionCenter::query()->orderBy('name')->value('id');
            session(['current_center_id' => $fallback]);
            app(CurrentCenter::class)->forget();
        }

        return count($ids);
    }

    private function validated(Request $request, ?InspectionCenter $center = null): array
    {
        if (filled($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('inspection_centers', 'code')->ignore($center?->id)],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['code'] = filled($validated['code'] ?? null) ? $validated['code'] : null;

        return $validated;
    }
}
