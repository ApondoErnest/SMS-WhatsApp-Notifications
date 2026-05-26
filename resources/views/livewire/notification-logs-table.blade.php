<div class="space-y-4">
    <div class="flex flex-wrap gap-3">
        <input type="text" wire:model.live.debounce.300ms="search"
            placeholder="{{ __('Customer, plate or phone…') }}"
            class="w-72 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
        <select wire:model.live="channelFilter"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <option value="">{{ __('All channels') }}</option>
            <option value="sms">SMS</option>
            <option value="whatsapp">WhatsApp</option>
        </select>
        <select wire:model.live="statusFilter"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <option value="">{{ __('All statuses') }}</option>
            <option value="pending">{{ __('Pending') }}</option>
            <option value="sent">{{ __('Sent') }}</option>
            <option value="delivered">{{ __('Delivered') }}</option>
            <option value="failed">{{ __('Failed') }}</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Customer') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Phone') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Channel') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Provider') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Error') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-600">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $log->inspectionRecord?->customer_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $log->phone_number }}</td>
                        <td class="px-4 py-3">
                            @if ($log->channel === 'sms')
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">SMS</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">WhatsApp</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $log->provider }}</td>
                        <td class="px-4 py-3">
                            @php
                                $sc = match($log->delivery_status) {
                                    'delivered' => 'bg-green-100 text-green-800',
                                    'sent' => 'bg-blue-100 text-blue-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ __(ucfirst($log->delivery_status)) }}</span>
                        </td>
                        <td class="max-w-[200px] truncate px-4 py-3 text-xs text-red-600">{{ $log->error_message ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('No notification logs.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</div>
