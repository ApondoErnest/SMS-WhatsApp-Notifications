@extends('layouts.app')

@section('title', __('Notifications'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Notification history') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('All SMS and WhatsApp sent to customers.') }}</p>
    </div>

    <livewire:notification-logs-table />
@endsection
