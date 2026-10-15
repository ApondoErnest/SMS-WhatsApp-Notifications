<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\InspectionCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('Invalid credentials.')]);
        }

        $user = auth()->user();

        if ($user->status !== 'active') {
            Auth::logout();

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('This account is inactive.')]);
        }

        if (! $user->isSuperAdmin() && $user->center && $user->center->status !== 'active') {
            Auth::logout();

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('This center is inactive.')]);
        }

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

        return redirect()->intended(route('dashboard'));
    }
}
