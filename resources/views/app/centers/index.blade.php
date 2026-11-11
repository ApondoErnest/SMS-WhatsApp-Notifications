@extends('layouts.app')

@section('title', __('Centers'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Inspection centers') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('Each center keeps its own users, imports, records, and templates.') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div
                class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2 shadow-sm"
                x-data="{
                    ok: false,
                    credit: null,
                    accountExpiresAt: null,
                    balanceExpiresAt: null,
                    message: null,
                    loading: true,
                    get formattedCredit() {
                        if (this.credit === null || this.credit === undefined) return '—';
                        return new Intl.NumberFormat().format(this.credit);
                    },
                    init() {
                        this.refresh(false);
                    },
                    async refresh(fresh = true) {
                        this.loading = true;
                        try {
                            const url = fresh
                                ? @js(route('sms.balance', ['fresh' => 1]))
                                : @js(route('sms.balance'));
                            const response = await fetch(url, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin',
                            });
                            const data = await response.json();
                            this.ok = !!data.ok;
                            this.credit = data.credit ?? null;
                            this.accountExpiresAt = data.account_expires_at ?? null;
                            this.balanceExpiresAt = data.balance_expires_at ?? null;
                            this.message = data.message ?? null;
                        } catch (e) {
                            this.ok = false;
                            this.message = @js(__('Could not refresh SMS balance.'));
                        } finally {
                            this.loading = false;
                        }
                    },
                }"
            >
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ __('SMS balance') }}</p>
                    <template x-if="loading && !ok && !message">
                        <p class="text-sm font-medium text-slate-400">{{ __('Loading…') }}</p>
                    </template>
                    <template x-if="!loading && ok">
                        <p class="text-sm font-semibold text-slate-900">
                            <span x-text="formattedCredit"></span>
                            <span class="font-normal text-slate-500">{{ __('credits') }}</span>
                        </p>
                    </template>
                    <template x-if="!loading && !ok">
                        <p class="max-w-xs truncate text-sm font-medium text-red-600" x-text="message || @js(__('Unavailable'))" :title="message"></p>
                    </template>
                    <p class="text-xs text-slate-400" x-show="ok && balanceExpiresAt">
                        {{ __('Balance expires') }}: <span x-text="balanceExpiresAt"></span>
                    </p>
                </div>
                <button type="button"
                    @click="refresh(true)"
                    :disabled="loading"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 disabled:cursor-wait disabled:opacity-60">
                    <svg class="h-3.5 w-3.5" :class="loading && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="loading ? @js(__('Refreshing…')) : @js(__('Refresh'))"></span>
                </button>
            </div>
            <a href="{{ route('centers.create') }}"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                {{ __('New center') }}
            </a>
        </div>
    </div>

    @if ($errors->has('center_ids'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('center_ids') }}
        </div>
    @endif

    <form method="POST" action="{{ route('centers.destroy-many') }}"
        x-data="{ selected: [], all: @js($centers->pluck('id')->values()) }"
        @submit.prevent="if (selected.length === 0) { alert(@js(__('Select at least one center to delete.'))); return; } if (confirm(@js(__('Delete the selected centers? This permanently removes their users, imports, records, schedules, templates, and notification logs.')))) $el.submit()">
        @csrf
        @method('DELETE')

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox"
                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    @change="selected = $event.target.checked ? [...all] : []"
                    :checked="selected.length > 0 && selected.length === all.length">
                <span>{{ __('Select all') }}</span>
            </label>
            <button type="submit"
                class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="selected.length === 0">
                {{ __('Delete selected') }}
                <span x-show="selected.length > 0" x-text="'(' + selected.length + ')'"></span>
            </button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($centers as $center)
                <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm {{ $currentCenter?->id === $center->id ? 'ring-2 ring-indigo-500' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <label class="flex min-w-0 items-start gap-3">
                            <input type="checkbox" name="center_ids[]" value="{{ $center->id }}"
                                class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                x-model.number="selected">
                            <span class="min-w-0">
                                <span class="block truncate text-lg font-semibold text-slate-900">{{ $center->name }}</span>
                                <span class="mt-0.5 block text-sm text-slate-500">
                                    {{ $center->code ? $center->code.' · ' : '' }}{{ $center->city ?: __('No city') }}
                                </span>
                            </span>
                        </label>
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
                            <button type="submit" form="switch-center-{{ $center->id }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">{{ __('Work in this center') }}</button>
                        @endif
                        <div class="flex items-center gap-3">
                            <a href="{{ route('centers.edit', $center) }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                            <button type="submit" form="delete-center-{{ $center->id }}"
                                class="text-xs font-medium text-red-600 hover:text-red-800"
                                onclick="return confirm(@js(__('Delete this center? This permanently removes its users, imports, records, schedules, templates, and notification logs.')))">
                                {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                    {{ __('No centers yet.') }}
                </div>
            @endforelse
        </div>
    </form>

    @foreach ($centers as $center)
        @if ($currentCenter?->id !== $center->id)
            <form id="switch-center-{{ $center->id }}" method="POST" action="{{ route('centers.switch') }}" class="hidden">
                @csrf
                <input type="hidden" name="center_id" value="{{ $center->id }}">
            </form>
        @endif
        <form id="delete-center-{{ $center->id }}" method="POST" action="{{ route('centers.destroy', $center) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endsection
