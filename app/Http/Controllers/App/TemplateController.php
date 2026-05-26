<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        $centerId = auth()->user()->center_id;
        $templates = NotificationTemplate::where('center_id', $centerId)
            ->orderBy('channel')
            ->orderBy('language')
            ->get();

        $grouped = $templates->groupBy('channel');

        return view('app.templates.index', compact('templates', 'grouped'));
    }

    public function create(): View
    {
        return view('app.templates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'channel' => ['required', 'in:sms,whatsapp'],
            'language' => ['required', 'in:fr,en'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:1600'],
        ]);

        $centerId = auth()->user()->center_id;

        $exists = NotificationTemplate::where('center_id', $centerId)
            ->where('channel', $validated['channel'])
            ->where('language', $validated['language'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'channel' => __('A template already exists for this channel and language. Edit it instead.'),
            ]);
        }

        NotificationTemplate::create([
            'center_id' => $centerId,
            ...$validated,
            'status' => 'active',
        ]);

        return redirect()->route('templates.index')
            ->with('status', __('Template created successfully.'));
    }

    public function edit(NotificationTemplate $template): View
    {
        abort_if($template->center_id !== auth()->user()->center_id, 403);

        return view('app.templates.edit', compact('template'));
    }

    public function update(Request $request, NotificationTemplate $template): RedirectResponse
    {
        abort_if($template->center_id !== auth()->user()->center_id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:1600'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $template->update($validated);

        return redirect()->route('templates.index')
            ->with('status', __('Template updated successfully.'));
    }

    public function destroy(NotificationTemplate $template): RedirectResponse
    {
        abort_if($template->center_id !== auth()->user()->center_id, 403);

        $template->delete();

        return redirect()->route('templates.index')
            ->with('status', __('Template deleted.'));
    }
}
