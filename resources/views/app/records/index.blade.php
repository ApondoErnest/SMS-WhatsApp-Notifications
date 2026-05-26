@extends('layouts.app')

@section('title', __('Records'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Inspection records') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('All inspected vehicles with their expiry date.') }}</p>
    </div>

    <livewire:inspection-records-table />
@endsection
