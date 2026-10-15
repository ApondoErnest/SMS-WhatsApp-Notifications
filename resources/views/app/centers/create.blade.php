@extends('layouts.app')

@section('title', __('New center'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('centers.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← {{ __('Back to centers') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ __('New center') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('Create an inspection center. Its data stays separate from the others.') }}</p>
    </div>

    <form method="POST" action="{{ route('centers.store') }}" class="max-w-2xl space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @include('app.centers._form')
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('Create center') }}</button>
            <a href="{{ route('centers.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
