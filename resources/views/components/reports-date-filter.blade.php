@props(['from', 'to', 'action'])

<form method="GET" action="{{ $action }}"
    x-data="dateRangePicker({
        from: @js($from),
        to: @js($to),
        locale: @js(app()->getLocale()),
    })"
    @click.outside="closeAll()"
    class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    {{ $slot }}

    <input type="hidden" name="from" :value="from || ''">
    <input type="hidden" name="to" :value="to || ''">

    <div class="relative min-w-[168px]">
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</label>
        <button type="button" @click="toggleFrom()"
            class="flex w-full items-center gap-2 rounded-lg border bg-slate-50 px-3 py-2 text-left text-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            :class="openFrom ? 'border-indigo-500 bg-indigo-50/60' : 'border-slate-300 hover:border-indigo-300'">
            <svg class="h-4 w-4 shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="min-w-0 flex-1 truncate font-medium" :class="from ? 'text-slate-900' : 'text-slate-400'"
                x-text="formatDisplay(from) || @js(__('Choose start date'))"></span>
        </button>
        @include('components.partials.schedule-calendar', ['target' => 'from', 'placement' => 'popover'])
    </div>

    <div class="relative min-w-[168px]">
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</label>
        <button type="button" @click="toggleTo()"
            class="flex w-full items-center gap-2 rounded-lg border bg-slate-50 px-3 py-2 text-left text-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            :class="openTo ? 'border-indigo-500 bg-indigo-50/60' : 'border-slate-300 hover:border-indigo-300'">
            <svg class="h-4 w-4 shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="min-w-0 flex-1 truncate font-medium" :class="to ? 'text-slate-900' : 'text-slate-400'"
                x-text="formatDisplay(to) || @js(__('Choose end date'))"></span>
        </button>
        @include('components.partials.schedule-calendar', ['target' => 'to', 'placement' => 'popover'])
    </div>

    <button type="submit"
        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
        {{ __('Apply') }}
    </button>
</form>
