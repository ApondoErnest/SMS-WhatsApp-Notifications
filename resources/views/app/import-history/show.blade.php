@extends('layouts.app')

@section('title', __('Import details'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('import-history.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← {{ __('Back to history') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $batch->original_filename }}</h1>
        <p class="mt-1 text-sm text-slate-600">
            {{ __('Imported by') }} {{ $batch->uploader?->name ?? '—' }}
            {{ __('on') }} {{ $batch->created_at->format('d/m/Y H:i') }}
        </p>
    </div>

    {{-- Summary --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard-card :label="__('Total rows')" :value="number_format($batch->total_rows)" />
        <x-dashboard-card :label="__('Imported')" :value="number_format($batch->imported_rows)" color="green" />
        <x-dashboard-card :label="__('Duplicates')" :value="number_format($batch->duplicate_rows)" color="amber" />
        <x-dashboard-card :label="__('Failed')" :value="number_format($batch->failed_rows)" color="red" />
    </div>

    {{-- Imported records --}}
    <div class="mb-8">
        <h2 class="mb-3 text-lg font-semibold text-slate-900">{{ __('Imported records') }}</h2>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Plate') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Customer') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Phone') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Expiry') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-medium">{{ $record->licence_plate }}</td>
                            <td class="px-4 py-3">{{ $record->customer_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $record->normalized_phone_number }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $record->expiration_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $record->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">{{ __('No records.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <div class="mt-4">{{ $records->links() }}</div>
        @endif
    </div>

    {{-- Failed rows --}}
    @if ($failedRows->count())
        <div>
            <h2 class="mb-3 text-lg font-semibold text-red-800">{{ __('Failed rows') }}</h2>
            <div class="overflow-hidden rounded-xl border border-red-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-red-100 text-sm">
                    <thead class="bg-red-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-red-700">{{ __('Row') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-red-700">{{ __('Error') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-red-700">{{ __('Data') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-50">
                        @foreach ($failedRows as $row)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $row->row_number }}</td>
                                <td class="px-4 py-3 text-red-700">{{ $row->error_message }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <code class="rounded bg-slate-100 px-1 py-0.5">{{ json_encode($row->row_data, JSON_UNESCAPED_UNICODE) }}</code>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
