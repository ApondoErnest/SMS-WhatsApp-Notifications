<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InspectionCenter;
use App\Support\CurrentCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CenterController extends Controller
{
    public function index(CurrentCenter $currentCenter): View
    {
        $centers = InspectionCenter::query()
            ->withCount(['users', 'inspectionRecords', 'importBatches'])
            ->orderBy('name')
            ->get();

        return view('app.centers.index', [
            'centers' => $centers,
            'currentCenter' => $currentCenter->center(),
        ]);
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
