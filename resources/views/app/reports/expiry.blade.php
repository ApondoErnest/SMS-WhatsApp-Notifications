@extends('layouts.app')

@section('title', __('Expiry reports'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Expiry reports') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('Certificates expiring in the selected window.') }} ({{ number_format($count) }})</p>
        </div>
        <a href="{{ route('reports.expiry.export', request()->query()) }}"
            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
            {{ __('Export CSV') }}
        </a>
    </div>

    <x-reports-nav :from="$from" :to="$to" />

    <div class="mb-6 flex flex-wrap gap-2">
        @foreach ([
            'week' => __('This week'),
            'month' => __('This month'),
            'expired' => __('Already expired'),
            'custom' => __('Custom range'),
        ] as $key => $label)
            <a href="{{ route('reports.expiry', array_filter(['window' => $key, 'from' => $key === 'custom' ? $from : null, 'to' => $key === 'custom' ? $to : null])) }}"
                class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $window === $key ? 'bg-amber-100 text-amber-800' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($window === 'custom')
        <div class="mb-6">
            <x-reports-date-filter :from="$from" :to="$to" :action="route('reports.expiry')">
                <input type="hidden" name="window" value="custom">
            </x-reports-date-filter>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Licence plate') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Customer') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Phone') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Expiration date') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $record->licence_plate }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $record->customer_name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $record->normalized_phone_number ?: $record->phone_number }}</td>
                        <td class="px-4 py-3 text-amber-700 font-medium">{{ optional($record->expiration_date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $record->status }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('view-records')
                                <a href="{{ route('records.show', $record) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Details') }}</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">{{ __('No certificates in this window.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
