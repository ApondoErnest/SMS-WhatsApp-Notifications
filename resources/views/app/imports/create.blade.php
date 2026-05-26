@extends('layouts.app')

@section('title', __('Import CSV'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Import a CSV file') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __("Select the day's inspection file to import into the system.") }}</p>
    </div>

    <livewire:csv-import-upload />
@endsection
