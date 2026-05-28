<div class="space-y-4">
    {{-- Active period (always visible) --}}
    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-indigo-50 px-5 py-4 shadow-sm">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white shadow-md">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">{{ __('Period') }}</p>
            <p class="text-sm font-medium text-slate-600">{{ $period['label'] }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:gap-6">
            <div class="text-center sm:text-left">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</p>
                <p class="text-lg font-bold tabular-nums text-slate-900">{{ $period['from_display'] }}</p>
            </div>
            <svg class="hidden h-5 w-5 text-indigo-300 sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
            <div class="text-center sm:text-left">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</p>
                <p class="text-lg font-bold tabular-nums text-slate-900">{{ $period['to_display'] }}</p>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <select wire:model.live="statusFilter"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            <option value="">{{ __('All statuses') }}</option>
            <option value="pending">{{ __('Pending') }}</option>
            <option value="sent">{{ __('Sent') }}</option>
            <option value="failed">{{ __('Failed') }}</option>
        </select>
        <select wire:model.live="channelFilter"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            <option value="">{{ __('All channels') }}</option>
            <option value="sms">SMS</option>
            <option value="whatsapp">WhatsApp</option>
        </select>
        <select wire:model.live="dateFilter"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            <option value="">{{ __('All dates') }}</option>
            <option value="today">{{ __('Today') }}</option>
            <option value="week">{{ __('This week') }}</option>
            <option value="month">{{ __('This month') }}</option>
            <option value="custom">{{ __('Custom range') }}</option>
        </select>
    </div>

    @if ($dateFilter === 'custom')
        <div
            x-data="scheduleDateRange({
                from: @entangle('dateFrom').live,
                to: @entangle('dateTo').live,
                locale: @js(app()->getLocale()),
            })"
            @click.outside="closeAll()"
            class="max-w-2xl overflow-visible rounded-lg border border-slate-200 bg-white p-3 shadow-md"
        >
            <div class="mb-2 flex items-center justify-between border-b border-slate-100 pb-2">
                <div>
                    <h3 class="text-xs font-semibold text-slate-900">{{ __('Select date range') }}</h3>
                    <p class="text-[10px] text-slate-500">{{ __('Click a start date and an end date') }}</p>
                </div>
                <button type="button" @click="clearRange()"
                    class="rounded px-2 py-1 text-[10px] font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">
                    {{ __('Clear dates') }}
                </button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                {{-- From calendar --}}
                <div class="relative min-w-0">
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</label>
                    <button type="button" @click="toggleFrom()"
                        class="mb-1.5 flex w-full items-center justify-between rounded-lg border bg-slate-50 px-2.5 py-1.5 text-left transition"
                        :class="openFrom ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200 hover:border-indigo-300'">
                        <span class="truncate text-xs font-medium" :class="from ? 'text-slate-900' : 'text-slate-400'"
                            x-text="formatDisplay(from) || '{{ __('Choose start date') }}'"></span>
                        <svg class="h-3.5 w-3.5 shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    @include('components.partials.schedule-calendar', ['target' => 'from'])
                </div>

                {{-- To calendar --}}
                <div class="relative min-w-0">
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</label>
                    <button type="button" @click="toggleTo()"
                        class="mb-1.5 flex w-full items-center justify-between rounded-lg border bg-slate-50 px-2.5 py-1.5 text-left transition"
                        :class="openTo ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200 hover:border-indigo-300'">
                        <span class="truncate text-xs font-medium" :class="to ? 'text-slate-900' : 'text-slate-400'"
                            x-text="formatDisplay(to) || '{{ __('Choose end date') }}'"></span>
                        <svg class="h-3.5 w-3.5 shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    @include('components.partials.schedule-calendar', ['target' => 'to'])
                </div>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Customer') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Plate') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Vehicle category') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Channel') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Scheduled date') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Attempts') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($schedules as $schedule)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium">{{ $schedule->inspectionRecord?->customer_name ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $schedule->inspectionRecord?->licence_plate ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $schedule->inspectionRecord?->vehicle_category ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($schedule->channel === 'sms')
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">SMS</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">WhatsApp</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $schedule->scheduled_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $schedule->attempts }}/3</td>
                        <td class="px-4 py-3">
                            @php
                                $sc = match($schedule->status) {
                                    'sent' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    default => 'bg-amber-100 text-amber-800',
                                };
                            @endphp
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ __(ucfirst($schedule->status)) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('No schedules.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $schedules->links() }}</div>
</div>
