<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\InspectionCenter;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, AuditLogService $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request), (int) config('security.login.decay_seconds', 60));

            $audit->record(
                'login_failed',
                'Failed login for '.$credentials['email'],
                userId: null,
            );

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('Invalid credentials.')]);
        }

        $user = auth()->user();

        if ($user->status !== 'active') {
            Auth::logout();
            RateLimiter::hit($this->throttleKey($request), (int) config('security.login.decay_seconds', 60));

            $audit->record(
                'login_blocked',
                'Inactive account login blocked for '.$user->email,
                centerId: $user->center_id,
                userId: $user->id,
            );

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('This account is inactive.')]);
        }

        if (! $user->isSuperAdmin() && $user->center && $user->center->status !== 'active') {
            Auth::logout();
            RateLimiter::hit($this->throttleKey($request), (int) config('security.login.decay_seconds', 60));

            $audit->record(
                'login_blocked',
                'Inactive center login blocked for '.$user->email,
                centerId: $user->center_id,
                userId: $user->id,
            );

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('This center is inactive.')]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        if ($user->isSuperAdmin()) {
            $centerId = InspectionCenter::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->value('id');

            if ($centerId) {
                $request->session()->put('current_center_id', $centerId);
            }
        }

        $audit->record(
            'login_success',
            'User signed in',
            centerId: $user->center_id ?? session('current_center_id'),
            userId: $user->id,
        );

        return redirect()->intended(route('dashboard'));
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        $maxAttempts = (int) config('security.login.max_attempts', 5);

        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                'seconds' => $seconds,
            ]),
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
