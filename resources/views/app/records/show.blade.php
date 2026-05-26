@extends('layouts.app')

@section('title', $record->licence_plate . ' — ' . __('Record Details'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('records.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← {{ __('Back to records') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $record->licence_plate }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ $record->customer_name }} — {{ $record->normalized_phone_number }}</p>
    </div>

    {{-- Record info --}}
    <div class="mb-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">{{ __('Vehicle information') }}</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Plate') }}</dt>
                    <dd class="font-mono font-semibold text-slate-800">{{ $record->licence_plate }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Cat.') }}</dt>
                    <dd class="text-slate-800">{{ $record->vehicle_class ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Type') }}</dt>
                    <dd class="text-slate-800">{{ $record->inspection_type ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Category') }}</dt>
                    <dd class="text-slate-800">{{ $record->vehicle_category ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">{{ __('Inspection details') }}</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Registration') }}</dt>
                    <dd class="text-slate-800">{{ $record->registration_date?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Inspection') }}</dt>
                    <dd class="text-slate-800">{{ $record->inspection_date?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Expiry') }}</dt>
                    <dd class="font-semibold {{ $record->expiration_date?->isPast() ? 'text-red-600' : 'text-slate-800' }}">
                        {{ $record->expiration_date?->format('d/m/Y') ?? '—' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Status') }}</dt>
                    <dd>
                        @php
                            $sc = match($record->status) {
                                'APTE' => 'bg-green-100 text-green-800',
                                'INAPTE' => 'bg-red-100 text-red-800',
                                default => 'bg-amber-100 text-amber-800',
                            };
                        @endphp
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ $record->status }}</span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Import date') }}</dt>
                    <dd class="text-slate-800">{{ $record->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                @if ($record->importBatch)
                    <div class="flex justify-between">
                        <dt class="text-slate-500">{{ __('Import file') }}</dt>
                        <dd>
                            <a href="{{ route('import-history.show', $record->importBatch) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $record->importBatch->original_filename }}
                            </a>
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">{{ __('Customer') }}</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Name') }}</dt>
                    <dd class="font-medium text-slate-800">{{ $record->customer_name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Phone (raw)') }}</dt>
                    <dd class="text-slate-800">{{ $record->phone_number }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Phone (E.164)') }}</dt>
                    <dd class="font-mono text-slate-800">{{ $record->normalized_phone_number }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">{{ __('Record hash') }}</h2>
            <code class="break-all rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $record->record_hash }}</code>
        </div>
    </div>

    {{-- Notification schedules --}}
    <div class="mb-8">
        <h2 class="mb-3 text-lg font-semibold text-slate-900">{{ __('Notification schedules') }}</h2>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Channel') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Scheduled date') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Attempts') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($record->notificationSchedules as $schedule)
                        <tr>
                            <td class="px-4 py-3">
                                @if ($schedule->channel === 'sms')
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">SMS</span>
                                @else
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">WhatsApp</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $schedule->scheduled_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $schedule->attempts }}/3</td>
                            <td class="px-4 py-3">
                                @php
                                    $sc = match($schedule->status) {
                                        'sent' => 'bg-green-100 text-green-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        default => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ __(ucfirst($schedule->status)) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">{{ __('No schedules.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Notification logs --}}
    <div>
        <h2 class="mb-3 text-lg font-semibold text-slate-900">{{ __('Notification history') }}</h2>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Channel') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Provider') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Error') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($record->notificationLogs as $log)
                        <tr>
                            <td class="px-4 py-3 text-slate-600">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                @if ($log->channel === 'sms')
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">SMS</span>
                                @else
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">WhatsApp</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $log->provider }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $sc = match($log->delivery_status) {
                                        'delivered' => 'bg-green-100 text-green-800',
                                        'sent' => 'bg-blue-100 text-blue-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ __(ucfirst($log->delivery_status)) }}</span>
                            </td>
                            <td class="max-w-[200px] truncate px-4 py-3 text-xs text-red-600">{{ $log->error_message ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">{{ __('No notification logs.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
