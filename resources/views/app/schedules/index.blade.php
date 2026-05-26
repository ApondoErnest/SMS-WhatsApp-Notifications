@extends('layouts.app')

@section('title', __('Schedules'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Notification schedules') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('SMS and WhatsApp reminders scheduled before expiry.') }}</p>
    </div>

    <livewire:notification-schedules-table />
@endsection
