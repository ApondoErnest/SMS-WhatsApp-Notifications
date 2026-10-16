@extends('layouts.app')

@section('title', __('Edit center'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('centers.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← {{ __('Back to centers') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ __('Edit center') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ $center->name }}</p>
    </div>

    <form method="POST" action="{{ route('centers.update', $center) }}" class="max-w-2xl space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('app.centers._form', ['center' => $center])
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('Save changes') }}</button>
            <a href="{{ route('centers.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
        </div>
    </form>

    <form method="POST" action="{{ route('centers.destroy', $center) }}" class="mt-6 max-w-2xl rounded-2xl border border-red-200 bg-red-50/60 p-6"
        onsubmit="return confirm(@js(__('Delete this center? This permanently removes its users, imports, records, schedules, templates, and notification logs.')))">
        @csrf
        @method('DELETE')
        <h2 class="text-sm font-semibold text-red-800">{{ __('Delete center') }}</h2>
        <p class="mt-1 text-sm text-red-700">{{ __('This cannot be undone. Staff accounts for this center will also be deleted.') }}</p>
        <button type="submit" class="mt-4 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('Delete center') }}</button>
    </form>
@endsection
