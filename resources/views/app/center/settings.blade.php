@extends('layouts.app')

@section('title', __('Center profile'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Center profile') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('Details of the inspection center you are working in.') }}</p>
    </div>

    <div class="grid max-w-5xl gap-6 lg:grid-cols-2 lg:items-start">
        <form method="POST" action="{{ route('center.settings.update') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <h2 class="text-base font-semibold text-slate-900">{{ __('Profile') }}</h2>
            @include('app.centers._form', ['center' => $center, 'showStatus' => false])
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('Save changes') }}</button>
        </form>

        @php
            $fromOld = old('reminders_form') !== null;
            $selectedDays = array_map('intval', $fromOld ? (array) old('reminder_days', []) : $center->reminderDays());
            $smsEnabled = $fromOld ? (bool) old('sms_enabled') : $center->sms_enabled;
            $whatsappEnabled = $fromOld ? (bool) old('whatsapp_enabled') : $center->whatsapp_enabled;
        @endphp

        <form method="POST" action="{{ route('center.reminders.update') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <input type="hidden" name="reminders_form" value="1">

            <div>
                <h2 class="text-base font-semibold text-slate-900">{{ __('Reminders') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('Choose how and when customers are reminded before their inspection expires.') }}</p>
            </div>

            <fieldset class="space-y-3">
                <legend class="text-sm font-medium text-slate-700">{{ __('Channels') }}</legend>

                @foreach ([
                    ['name' => 'sms_enabled', 'label' => 'SMS', 'hint' => __('Text message via Africa\'s Talking'), 'checked' => $smsEnabled, 'color' => 'peer-checked:bg-blue-600'],
                    ['name' => 'whatsapp_enabled', 'label' => 'WhatsApp', 'hint' => __('Template message via Meta WhatsApp'), 'checked' => $whatsappEnabled, 'color' => 'peer-checked:bg-green-600'],
                ] as $channel)
                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-slate-200 px-4 py-3 hover:bg-slate-50">
                        <span>
                            <span class="block text-sm font-medium text-slate-900">{{ $channel['label'] }}</span>
                            <span class="block text-xs text-slate-500">{{ $channel['hint'] }}</span>
                        </span>
                        <span class="relative inline-flex shrink-0 items-center">
                            <input type="hidden" name="{{ $channel['name'] }}" value="0">
                            <input type="checkbox" name="{{ $channel['name'] }}" value="1" class="peer sr-only" @checked($channel['checked'])>
                            <span class="h-6 w-11 rounded-full bg-slate-300 transition {{ $channel['color'] }} peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500/40"></span>
                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                @endforeach
            </fieldset>

            <fieldset>
                <legend class="text-sm font-medium text-slate-700">{{ __('Send a reminder this many days before expiry') }}</legend>
                <div class="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-6">
                    @foreach ($dayOptions as $day)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="reminder_days[]" value="{{ $day }}" class="peer sr-only" @checked(in_array($day, $selectedDays, true))>
                            <span class="flex h-10 items-center justify-center rounded-lg border border-slate-200 text-sm font-medium tabular-nums text-slate-700 transition hover:border-indigo-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500/40">
                                {{ $day }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ __('Days are counted back from the expiration date. Reminders whose date has already passed are not sent.') }}</p>
                @error('reminder_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('reminder_days.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </fieldset>

            <div class="rounded-lg bg-slate-50 px-4 py-3 text-xs text-slate-600">
                {{ __('Saving updates the upcoming reminders of all current records. Refused inspections and vehicles that have already renewed get no reminders.') }}
            </div>

            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('Save reminder settings') }}</button>
        </form>
    </div>
@endsection
