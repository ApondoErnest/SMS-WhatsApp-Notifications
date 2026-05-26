@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Dashboard') }}</h1>
            <p class="mt-1 text-sm text-slate-600">
                {{ __('Welcome') }}, {{ auth()->user()->name }}
            </p>
        </div>
        @can('import-csv')
            <a href="{{ route('imports.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                <x-icon-upload class="h-4 w-4" />
                {{ __('Import CSV') }}
            </a>
        @endcan
    </div>

    {{-- Stats grid --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-dashboard-card :label="__('Total records')" :value="number_format($totalRecords)" />
        <x-dashboard-card :label="__('Imported today')" :value="number_format($importedToday)" />
        <x-dashboard-card :label="__('Duplicates skipped')" :value="number_format($totalDuplicates)" />
        <x-dashboard-card :label="__('Failed rows')" :value="number_format($totalFailed)" color="red" />
        <x-dashboard-card :label="__('Expiring this week')" :value="number_format($expiringThisWeek)" color="amber" />
        <x-dashboard-card :label="__('Expiring this month')" :value="number_format($expiringThisMonth)" color="amber" />
        <x-dashboard-card :label="__('SMS sent today')" :value="number_format($smsSentToday)" color="green" />
        <x-dashboard-card :label="__('WhatsApp today')" :value="number_format($whatsappSentToday)" color="green" />
        <x-dashboard-card :label="__('Failed notifications')" :value="number_format($failedNotifications)" color="red" />
    </div>

    {{-- Recent imports --}}
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('Recent imports') }}</h2>
            <a href="{{ route('import-history.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('View all') }} →</a>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('File') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Imported by') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Imported') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentBatches as $batch)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $batch->original_filename }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $batch->uploader?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-right text-green-700">{{ number_format($batch->imported_rows) }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $sc = match($batch->status) { 'completed' => 'bg-green-100 text-green-800', 'processing' => 'bg-amber-100 text-amber-800', 'failed' => 'bg-red-100 text-red-800', default => 'bg-slate-100 text-slate-700' };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ __(ucfirst($batch->status)) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">{{ __('No imports yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
