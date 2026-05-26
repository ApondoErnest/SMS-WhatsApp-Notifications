@extends('layouts.app')

@section('title', __('New template'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('templates.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← {{ __('Back to templates') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ __('New template') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('Create a new SMS or WhatsApp message template.') }}</p>
    </div>

    <form method="POST" action="{{ route('templates.store') }}" class="max-w-2xl space-y-6">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="channel" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Channel') }}</label>
                <select id="channel" name="channel" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="sms" {{ old('channel') === 'sms' ? 'selected' : '' }}>SMS</option>
                    <option value="whatsapp" {{ old('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                </select>
                @error('channel')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="language" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Language') }}</label>
                <select id="language" name="language" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="fr" {{ old('language', 'fr') === 'fr' ? 'selected' : '' }}>{{ __('French') }}</option>
                    <option value="en" {{ old('language') === 'en' ? 'selected' : '' }}>{{ __('English') }}</option>
                </select>
                @error('language')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="title" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Title') }}</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                placeholder="{{ __('e.g. Expiry reminder') }}">
            @error('title')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="content" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Message content') }}</label>
            <textarea id="content" name="content" rows="5" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                placeholder="{{ __('Write your message here…') }}">{{ old('content') }}</textarea>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('Available variables:') }} <code class="text-indigo-600">{customer_name}</code>, <code class="text-indigo-600">{licence_plate}</code>, <code class="text-indigo-600">{expiration_date}</code>
            </p>
            @error('content')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ __('Create template') }}
            </button>
            <a href="{{ route('templates.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
@endsection
