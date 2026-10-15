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
@endsection
