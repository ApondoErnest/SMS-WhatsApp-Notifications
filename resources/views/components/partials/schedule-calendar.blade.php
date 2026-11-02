@props([
    'target' => 'from',
    'placement' => 'inline', // inline | popover
])

@php
    $viewName = $target === 'from' ? 'viewFrom' : 'viewTo';
    $selectMethod = $target === 'from' ? 'selectFrom' : 'selectTo';
    $openExpr = $target === 'from' ? 'openFrom' : 'openTo';
    $panelClass = $placement === 'popover'
        ? 'absolute left-0 top-full z-50 mt-2 w-[280px]'
        : 'relative w-full max-w-[280px]';
@endphp

<div x-show="{{ $openExpr }}" x-cloak x-transition.origin.top.left
    @click.stop
    class="{{ $panelClass }} overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xl shadow-slate-200/70 ring-1 ring-black/5">
    <div class="border-b border-slate-100 bg-gradient-to-b from-slate-50 to-white px-3 pb-3 pt-3">
        <div class="mb-2 flex items-center gap-1">
            <button type="button" @click="prevMonth('{{ $target }}')"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-700"
                title="{{ __('Previous month') }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <select x-model.number="{{ $viewName }}.month"
                class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-7 text-xs font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                <template x-for="(name, index) in monthNames()" :key="'m-{{ $target }}-' + index">
                    <option :value="index" x-text="name"></option>
                </template>
            </select>
            <button type="button" @click="nextMonth('{{ $target }}')"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-700"
                title="{{ __('Next month') }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
        <div class="flex items-center gap-2">
            <select x-model.number="{{ $viewName }}.year"
                class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-7 text-xs font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                <template x-for="y in years()" :key="'y-{{ $target }}-' + y">
                    <option :value="y" x-text="y"></option>
                </template>
            </select>
            <button type="button" @click="goToToday('{{ $target }}')"
                class="shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">
                {{ __('Today') }}
            </button>
        </div>
    </div>

    <div class="px-3 pb-3 pt-2">
        <div class="mb-1.5 grid grid-cols-7">
            <template x-for="(label, i) in weekdayLabels()" :key="'w-{{ $target }}-' + i">
                <div class="py-1 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400" x-text="label"></div>
            </template>
        </div>
        <div class="grid grid-cols-7 gap-0.5">
            <template x-for="cell in daysFor({{ $viewName }})" :key="cell.key">
                <div class="flex h-9 items-center justify-center">
                    <button type="button"
                        x-show="!cell.empty"
                        @click="{{ $selectMethod }}(cell.iso)"
                        class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-medium transition"
                        :class="dayClasses(cell)"
                        x-text="cell.day"></button>
                </div>
            </template>
        </div>

        <button type="button" @click="selectToday('{{ $target }}')"
            class="mt-2 w-full rounded-xl border border-indigo-200 bg-indigo-50 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">
            {{ __('Select today') }}
        </button>
    </div>
</div>
