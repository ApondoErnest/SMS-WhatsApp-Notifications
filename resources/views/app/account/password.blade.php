@extends('layouts.app')

@section('title', __('Change password'))

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Change password') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('Update your login password. You will use the new password on your next sign-in.') }}</p>
        </div>

        <form method="POST" action="{{ route('account.password.update') }}"
            class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-700">{{ __('Current password') }}</label>
                <input type="password" name="current_password" id="current_password" required autocomplete="current-password"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                @error('current_password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">{{ __('New password') }}</label>
                <input type="password" name="password" id="password" required autocomplete="new-password"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">{{ __('Confirm new password') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    {{ __('Update password') }}
                </button>
                <a href="{{ route('dashboard') }}"
                    class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
@endsection
