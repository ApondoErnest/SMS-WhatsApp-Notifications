@extends('layouts.app')

@section('title', __('Import history'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Import history') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('All CSV files imported into the system.') }}</p>
        </div>
        @can('import-csv')
            <a href="{{ route('imports.create') }}"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                {{ __('New import') }}
            </a>
        @endcan
    </div>

    <livewire:import-history-table />
@endsection
