@extends('layouts.app')

@section('title', __('Center profile'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Center profile') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('Details of the inspection center you are working in.') }}</p>
    </div>

    <form method="POST" action="{{ route('center.settings.update') }}" class="max-w-2xl space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('app.centers._form', ['center' => $center, 'showStatus' => false])
        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('Save changes') }}</button>
    </form>
@endsection
