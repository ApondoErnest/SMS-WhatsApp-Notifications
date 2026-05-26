<div class="space-y-4">
    <div class="flex flex-wrap gap-3">
        <select wire:model.live="statusFilter"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <option value="">{{ __('All statuses') }}</option>
            <option value="pending">{{ __('Pending') }}</option>
            <option value="sent">{{ __('Sent') }}</option>
            <option value="failed">{{ __('Failed') }}</option>
        </select>
        <select wire:model.live="channelFilter"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <option value="">{{ __('All channels') }}</option>
            <option value="sms">SMS</option>
            <option value="whatsapp">WhatsApp</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Customer') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Plate') }}</th>
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
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No schedules.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $schedules->links() }}</div>
</div>
