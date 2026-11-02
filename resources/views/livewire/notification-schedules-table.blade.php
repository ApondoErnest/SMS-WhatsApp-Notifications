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
            <option value="queued">{{ __('Queued') }}</option>
            <option value="sent">{{ __('Sent') }}</option>
            <option value="failed">{{ __('Failed') }}</option>
            <option value="missed">{{ __('Missed') }}</option>
            <option value="cancelled">{{ __('Cancelled') }}</option>
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
            x-data="dateRangePicker({
                from: @entangle('dateFrom').live,
                to: @entangle('dateTo').live,
                locale: @js(app()->getLocale()),
            })"
            @click.outside="closeAll()"
            class="max-w-2xl overflow-visible rounded-2xl border border-slate-200 bg-white p-4 shadow-md"
        >
            <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Select date range') }}</h3>
                    <p class="text-xs text-slate-500">{{ __('Click a start date and an end date') }}</p>
                </div>
                <button type="button" @click="clearRange()"
                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">
                    {{ __('Clear dates') }}
                </button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="relative min-w-0">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</label>
                    <button type="button" @click="toggleFrom()"
                        class="mb-2 flex w-full items-center gap-2 rounded-lg border bg-slate-50 px-3 py-2 text-left transition"
                        :class="openFrom ? 'border-indigo-500 bg-indigo-50/60' : 'border-slate-200 hover:border-indigo-300'">
                        <svg class="h-4 w-4 shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="truncate text-sm font-medium" :class="from ? 'text-slate-900' : 'text-slate-400'"
                            x-text="formatDisplay(from) || @js(__('Choose start date'))"></span>
                    </button>

                    @include('components.partials.schedule-calendar', ['target' => 'from', 'placement' => 'inline'])
                </div>

                <div class="relative min-w-0">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</label>
                    <button type="button" @click="toggleTo()"
                        class="mb-2 flex w-full items-center gap-2 rounded-lg border bg-slate-50 px-3 py-2 text-left transition"
                        :class="openTo ? 'border-indigo-500 bg-indigo-50/60' : 'border-slate-200 hover:border-indigo-300'">
                        <svg class="h-4 w-4 shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="truncate text-sm font-medium" :class="to ? 'text-slate-900' : 'text-slate-400'"
                            x-text="formatDisplay(to) || @js(__('Choose end date'))"></span>
                    </button>

                    @include('components.partials.schedule-calendar', ['target' => 'to', 'placement' => 'inline'])
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
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Reminder') }}</th>
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
                        <td class="px-4 py-3 text-slate-600">
                            {{ $schedule->days_before !== null ? trans_choice(':count day before|:count days before', $schedule->days_before) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $schedule->attempts }}/3</td>
                        <td class="px-4 py-3">
                            @php
                                $sc = match($schedule->status) {
                                    'sent' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'queued' => 'bg-indigo-100 text-indigo-800',
                                    'missed', 'cancelled' => 'bg-slate-100 text-slate-600',
                                    default => 'bg-amber-100 text-amber-800',
                                };
                            @endphp
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ __(ucfirst($schedule->status)) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">{{ __('No schedules.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $schedules->links() }}</div>
</div>
