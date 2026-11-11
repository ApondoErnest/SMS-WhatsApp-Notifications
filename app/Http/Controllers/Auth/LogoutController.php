<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function destroy(Request $request, AuditLogService $audit): RedirectResponse
    {
        $user = $request->user();

        $audit->record(
            'logout',
            'User signed out',
            centerId: $user?->center_id ?? session('current_center_id'),
            userId: $user?->id,
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
