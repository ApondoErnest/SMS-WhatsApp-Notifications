@extends('layouts.app')

@section('title', __('Notification reports'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Notification reports') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('SMS and WhatsApp delivery results for the selected period.') }}</p>
        </div>
        <a href="{{ route('reports.notifications.export', request()->query()) }}"
            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
            {{ __('Export CSV') }}
        </a>
    </div>

    <x-reports-nav :from="$from" :to="$to" />

    <div class="mb-6">
        <x-reports-date-filter :from="$from" :to="$to" :action="route('reports.notifications')">
            <div>
                <label for="channel" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Channel') }}</label>
                <select id="channel" name="channel"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">{{ __('All channels') }}</option>
                    <option value="sms" @selected($channel === 'sms')>SMS</option>
                    <option value="whatsapp" @selected($channel === 'whatsapp')>WhatsApp</option>
                </select>
            </div>
            <div>
                <label for="status" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                <select id="status" name="status"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (['sent', 'delivered', 'read', 'failed', 'skipped'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ __(ucfirst($option)) }}</option>
                    @endforeach
                </select>
            </div>
        </x-reports-date-filter>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard-card :label="__('SMS delivered')" :value="number_format($sms['delivered'])" color="green" />
        <x-dashboard-card :label="__('WhatsApp delivered')" :value="number_format($whatsapp['delivered'] + $whatsapp['read'])" color="green" />
        <x-dashboard-card :label="__('Failed notifications')" :value="number_format($failedNotifications)" color="red" />
        <x-dashboard-card :label="__('SMS failed')" :value="number_format($sms['failed'])" color="red" />
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Channel') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Customer') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Phone') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Error') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-600">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $log->channel === 'sms' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ strtoupper($log->channel) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-800">
                            {{ $log->inspectionRecord?->customer_name ?? '—' }}
                            @if ($log->inspectionRecord?->licence_plate)
                                <span class="block text-xs text-slate-500">{{ $log->inspectionRecord->licence_plate }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $log->phone_number }}</td>
                        <td class="px-4 py-3">
                            @php
                                $sc = match($log->delivery_status) {
                                    'delivered', 'read' => 'bg-green-100 text-green-800',
                                    'sent' => 'bg-blue-100 text-blue-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'skipped' => 'bg-slate-100 text-slate-700',
                                    default => 'bg-amber-100 text-amber-800',
                                };
                            @endphp
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ __(ucfirst($log->delivery_status)) }}</span>
                        </td>
                        <td class="max-w-xs truncate px-4 py-3 text-slate-500" title="{{ $log->error_message }}">{{ $log->error_message ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">{{ __('No notifications in this period.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
