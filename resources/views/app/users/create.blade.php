@extends('layouts.app')

@section('title', __('New user'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← {{ __('Back to users') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ __('New user') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('Create an administrator or operator account.') }}</p>
    </div>

    <form method="POST" action="{{ route('users.store') }}" class="max-w-xl space-y-6">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">{{ __('Email address') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-slate-700">{{ __('Phone') }} <span class="text-slate-400">({{ __('optional') }})</span></label>
            <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">{{ __('Password') }}</label>
            <input type="password" name="password" id="password" required
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="role" class="block text-sm font-medium text-slate-700">{{ __('Account type') }}</label>
            <select name="role" id="role" required
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                <option value="operator" @selected(old('role') === 'operator')>{{ __('Operator') }}</option>
                <option value="admin" @selected(old('role') === 'admin')>{{ __('Administrator') }}</option>
            </select>
            @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ __('Create user') }}
            </button>
            <a href="{{ route('users.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
@endsection
