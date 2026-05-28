@php
    $viewName = $target === 'from' ? 'viewFrom' : 'viewTo';
    $selectMethod = $target === 'from' ? 'selectFrom' : 'selectTo';
@endphp

<div x-show="open{{ $target === 'from' ? 'From' : 'To' }}" x-cloak x-transition
    class="w-full max-w-[240px] rounded-lg border border-slate-200 bg-white p-2 shadow-lg">
    {{-- Month / year controls --}}
    <div class="mb-1.5 space-y-1.5">
        <div class="flex items-center gap-1">
            <button type="button" @click="prevMonth('{{ $target }}')"
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-slate-500 hover:bg-indigo-50 hover:text-indigo-700"
                title="{{ __('Previous month') }}">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <select x-model.number="{{ $viewName }}.month"
                class="min-w-0 flex-1 rounded border border-slate-200 bg-white py-0.5 pl-1 pr-6 text-[10px] font-medium text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/30">
                <template x-for="(name, index) in monthNames()" :key="'m-{{ $target }}-' + index">
                    <option :value="index" x-text="name"></option>
                </template>
            </select>
            <button type="button" @click="nextMonth('{{ $target }}')"
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-slate-500 hover:bg-indigo-50 hover:text-indigo-700"
                title="{{ __('Next month') }}">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
        <div class="flex items-center gap-1.5">
            <label class="shrink-0 text-[9px] font-semibold uppercase text-slate-400">{{ __('Year') }}</label>
            <select x-model.number="{{ $viewName }}.year"
                class="min-w-0 flex-1 rounded border border-slate-200 bg-white py-0.5 pl-1 pr-6 text-[10px] font-medium text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/30">
                <template x-for="y in years()" :key="'y-{{ $target }}-' + y">
                    <option :value="y" x-text="y"></option>
                </template>
            </select>
            <button type="button" @click="goToToday('{{ $target }}')"
                class="shrink-0 rounded border border-slate-200 px-1.5 py-0.5 text-[9px] font-medium text-slate-600 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">
                {{ __('Today') }}
            </button>
        </div>
    </div>

    {{-- Day grid --}}
    <div class="mb-1 grid grid-cols-7 border-b border-slate-100 pb-1">
        <template x-for="(label, i) in weekdayLabels()" :key="'w-{{ $target }}-' + i">
            <div class="text-center text-[9px] font-semibold uppercase text-slate-400" x-text="label"></div>
        </template>
    </div>
    <div class="grid grid-cols-7 gap-px">
        <template x-for="cell in daysFor({{ $viewName }})" :key="cell.key">
            <div class="flex h-7 items-center justify-center">
                <button type="button"
                    x-show="!cell.empty"
                    @click="{{ $selectMethod }}(cell.iso)"
                    class="flex h-7 w-7 items-center justify-center rounded-full text-[11px] transition"
                    :class="dayClasses(cell)"
                    x-text="cell.day"></button>
            </div>
        </template>
    </div>

    <button type="button" @click="selectToday('{{ $target }}')"
        class="mt-1.5 w-full rounded border border-indigo-200 bg-indigo-50 py-1 text-[10px] font-semibold text-indigo-700 hover:bg-indigo-100">
        {{ __('Select today') }}
    </button>
</div>
