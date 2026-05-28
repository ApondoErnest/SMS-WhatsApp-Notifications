<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->where('center_id', auth()->user()->center_id)
            ->orderBy('name')
            ->get();

        return view('app.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('app.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'operator'])],
        ]);

        $user = User::create([
            'center_id' => auth()->user()->center_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => 'active',
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')
            ->with('status', __('User created successfully.'));
    }

    public function edit(User $user): View
    {
        $this->ensureSameCenter($user);

        return view('app.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureSameCenter($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'operator'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            return back()->withErrors(['role' => __('You cannot remove your own administrator role.')]);
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'status' => $validated['status'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')
            ->with('status', __('User updated successfully.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureSameCenter($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('status', __('User deleted.'));
    }

    private function ensureSameCenter(User $user): void
    {
        if ($user->center_id !== auth()->user()->center_id) {
            abort(403);
        }
    }
}
