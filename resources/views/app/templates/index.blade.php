@extends('layouts.app')

@section('title', __('Templates'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Message templates') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('SMS and WhatsApp templates for expiry reminders.') }}</p>
        </div>
        <a href="{{ route('templates.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('New template') }}
        </a>
    </div>

    {{-- SMS templates --}}
    @if (isset($grouped['sms']) && $grouped['sms']->count())
        <div class="mb-8">
            <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-slate-900">
                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">SMS</span>
                {{ __('SMS Templates') }}
            </h2>
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($grouped['sms'] as $template)
                    @include('app.templates._card', ['template' => $template])
                @endforeach
            </div>
        </div>
    @endif

    {{-- WhatsApp templates --}}
    @if (isset($grouped['whatsapp']) && $grouped['whatsapp']->count())
        <div class="mb-8">
            <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-slate-900">
                <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">WhatsApp</span>
                {{ __('WhatsApp Templates') }}
            </h2>
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($grouped['whatsapp'] as $template)
                    @include('app.templates._card', ['template' => $template])
                @endforeach
            </div>
        </div>
    @endif

    @if ($templates->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="mt-4 text-slate-500">{{ __('No templates configured. Add SMS and WhatsApp templates in settings.') }}</p>
            <a href="{{ route('templates.create') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Create your first template') }} →</a>
        </div>
    @endif

    {{-- Preview section --}}
    @if ($templates->isNotEmpty())
        <div class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-2 text-lg font-semibold text-slate-900">{{ __('Message preview') }}</h2>
            <p class="mb-4 text-sm text-slate-500">{{ __('This is how messages will appear to customers (with sample data):') }}</p>
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($templates->where('status', 'active') as $template)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-2 flex items-center gap-2">
                            @if ($template->channel === 'sms')
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">SMS</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">WhatsApp</span>
                            @endif
                            <span class="text-xs font-medium text-slate-500">{{ strtoupper($template->language) }}</span>
                        </div>
                        <p class="whitespace-pre-wrap text-sm text-slate-700">{{ str_replace(['{licence_plate}', '{expiration_date}', '{customer_name}'], ['LT 1234 AB', '15/12/2026', 'Jean Dupont'], $template->content) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
