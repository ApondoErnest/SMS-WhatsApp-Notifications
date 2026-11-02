@extends('layouts.app')

@section('title', __('Import reports'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Import reports') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('CSV import batches and row outcomes for the selected period.') }}</p>
    </div>

    <x-reports-nav :from="$from" :to="$to" />

    <div class="mb-6">
        <x-reports-date-filter :from="$from" :to="$to" :action="route('reports.imports')" />
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard-card :label="__('Import batches')" :value="number_format($summary['batches'])" />
        <x-dashboard-card :label="__('Rows imported')" :value="number_format($summary['imported_rows'])" color="green" />
        <x-dashboard-card :label="__('Duplicates skipped')" :value="number_format($summary['duplicate_rows'])" color="amber" />
        <x-dashboard-card :label="__('Failed import rows')" :value="number_format($summary['failed_rows'])" color="red" />
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('File') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Imported by') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Total') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Imported') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Duplicates') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Failed') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($batches as $batch)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $batch->original_filename }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $batch->uploader?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($batch->total_rows) }}</td>
                        <td class="px-4 py-3 text-right text-green-700">{{ number_format($batch->imported_rows) }}</td>
                        <td class="px-4 py-3 text-right text-amber-700">{{ number_format($batch->duplicate_rows) }}</td>
                        <td class="px-4 py-3 text-right text-red-700">{{ number_format($batch->failed_rows) }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ __(ucfirst($batch->status)) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('import-history.show', $batch) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Details') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-slate-500">{{ __('No imports in this period.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $batches->links() }}</div>
@endsection
