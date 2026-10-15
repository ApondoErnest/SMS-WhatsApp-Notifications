<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
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

        return view('app.center.settings', compact('center'));
    }

    public function update(Request $request, CurrentCenter $currentCenter): RedirectResponse
    {
        $center = $currentCenter->center();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('inspection_centers', 'code')->ignore($center->id)],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['code'] = filled($validated['code'] ?? null)
            ? strtoupper(trim($validated['code']))
            : null;

        $center->update($validated);

        return redirect()->route('center.settings.edit')
            ->with('status', __('Center profile updated.'));
    }
}
