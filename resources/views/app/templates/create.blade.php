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
                {{ __('Available variables:') }}
                <code class="text-indigo-600">{customer_name}</code>,
                <code class="text-indigo-600">{licence_plate}</code>,
                <code class="text-indigo-600">{expiration_date}</code>,
                <code class="text-indigo-600">{center_name}</code>
            </p>
            @error('content')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div id="meta-fields" class="grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <p class="text-sm font-medium text-slate-800">{{ __('Meta WhatsApp template') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('Must match an approved template in Meta Business Manager. Named variables: customer_name, licence_plate, expiration_date, center_name.') }}</p>
            </div>
            <div>
                <label for="meta_template_name" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Meta template name') }}</label>
                <input id="meta_template_name" name="meta_template_name" type="text" value="{{ old('meta_template_name', 'visite_expiry_reminder') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    placeholder="visite_expiry_reminder">
                @error('meta_template_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="meta_language_code" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Meta language code') }}</label>
                <input id="meta_language_code" name="meta_language_code" type="text" value="{{ old('meta_language_code', 'fr') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    placeholder="fr">
                @error('meta_language_code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
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

@push('scripts')
<script>
    const channel = document.getElementById('channel');
    const metaFields = document.getElementById('meta-fields');
    const syncMeta = () => { metaFields.style.display = channel.value === 'whatsapp' ? '' : 'none'; };
    channel.addEventListener('change', syncMeta);
    syncMeta();
</script>
@endpush
