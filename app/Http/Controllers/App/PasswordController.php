<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('app.account.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        app(AuditLogService::class)->record(
            'password_changed',
            'User changed their password',
            centerId: $request->user()->center_id ?? session('current_center_id'),
        );

        return redirect()->route('account.password.edit')
            ->with('status', __('Password updated successfully.'));
    }
}
