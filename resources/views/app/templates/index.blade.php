@extends('layouts.app')

@section('title', __('Templates'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Message templates') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('SMS and WhatsApp templates for expiry reminders.') }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @forelse ($templates as $template)
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        @if ($template->channel === 'sms')
                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">SMS</span>
                        @else
                            <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">WhatsApp</span>
                        @endif
                        <span class="text-xs text-slate-500">{{ strtoupper($template->language) }}</span>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $template->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                        {{ $template->status === 'active' ? __('Active') : __('Inactive') }}
                    </span>
                </div>
                <h3 class="font-semibold text-slate-800">{{ $template->title }}</h3>
                <p class="mt-2 whitespace-pre-wrap text-sm text-slate-600">{{ $template->content }}</p>
                <p class="mt-3 text-xs text-slate-400">
                    {{ __('Variables:') }} <code class="text-indigo-600">{licence_plate}</code>, <code class="text-indigo-600">{expiration_date}</code>, <code class="text-indigo-600">{customer_name}</code>
                </p>
            </div>
        @empty
            <div class="col-span-2 rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center">
                <p class="text-slate-500">{{ __('No templates configured. Add SMS and WhatsApp templates in settings.') }}</p>
            </div>
        @endforelse
    </div>
@endsection
