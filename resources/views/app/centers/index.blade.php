@extends('layouts.app')

@section('title', __('Centers'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Inspection centers') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('Each center keeps its own users, imports, records, and templates.') }}</p>
        </div>
        <a href="{{ route('centers.create') }}"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            {{ __('New center') }}
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($centers as $center)
            <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm {{ $currentCenter?->id === $center->id ? 'ring-2 ring-indigo-500' : '' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-lg font-semibold text-slate-900">{{ $center->name }}</p>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ $center->code ? $center->code.' · ' : '' }}{{ $center->city ?: __('No city') }}
                        </p>
                    </div>
                    @if ($center->status === 'active')
                        <span class="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Active') }}</span>
                    @else
                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ __('Inactive') }}</span>
                    @endif
                </div>

                <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-slate-50 px-2 py-2">
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('Users') }}</dt>
                        <dd class="text-lg font-bold text-slate-900">{{ $center->users_count }}</dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-2 py-2">
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('Records') }}</dt>
                        <dd class="text-lg font-bold text-slate-900">{{ $center->inspection_records_count }}</dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-2 py-2">
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('Imports') }}</dt>
                        <dd class="text-lg font-bold text-slate-900">{{ $center->import_batches_count }}</dd>
                    </div>
                </dl>

                <div class="mt-4 flex items-center justify-between gap-2 border-t border-slate-100 pt-4">
                    @if ($currentCenter?->id === $center->id)
                        <span class="text-xs font-semibold text-indigo-700">{{ __('Working in this center') }}</span>
                    @else
                        <form method="POST" action="{{ route('centers.switch') }}">
                            @csrf
                            <input type="hidden" name="center_id" value="{{ $center->id }}">
                            <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">{{ __('Work in this center') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('centers.edit', $center) }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                {{ __('No centers yet.') }}
            </div>
        @endforelse
    </div>
@endsection
