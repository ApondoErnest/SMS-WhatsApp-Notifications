<div class="space-y-4">
    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <input type="text" wire:model.live.debounce.300ms="search"
            placeholder="{{ __('Customer, plate or phone…') }}"
            class="w-72 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <select wire:model.live="statusFilter"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <option value="">{{ __('All statuses') }}</option>
            <option value="APTE">APTE</option>
            <option value="INAPTE">INAPTE</option>
            <option value="CONTRE VISITE">CONTRE VISITE</option>
        </select>
        <select wire:model.live="expiryFilter"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <option value="">{{ __('All expirations') }}</option>
            <option value="this_week">{{ __('This week') }}</option>
            <option value="this_month">{{ __('This month') }}</option>
            <option value="expired">{{ __('Expired') }}</option>
        </select>
        <select wire:model.live="batchFilter"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <option value="">{{ __('All imports') }}</option>
            @foreach ($batches as $batch)
                <option value="{{ $batch->id }}">{{ $batch->original_filename }} ({{ $batch->created_at->format('d/m/Y') }})</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="cursor-pointer px-3 py-3 text-left font-medium text-slate-600" wire:click="sortBy('registration_date')">
                        {{ __('Registration') }}
                        @if ($sortField === 'registration_date')
                            <span class="text-indigo-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="cursor-pointer px-3 py-3 text-left font-medium text-slate-600" wire:click="sortBy('inspection_date')">
                        {{ __('Inspection') }}
                        @if ($sortField === 'inspection_date')
                            <span class="text-indigo-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="cursor-pointer px-3 py-3 text-left font-medium text-slate-600" wire:click="sortBy('expiration_date')">
                        {{ __('Expiry') }}
                        @if ($sortField === 'expiration_date')
                            <span class="text-indigo-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-3 py-3 text-left font-medium text-slate-600">{{ __('Cat.') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-slate-600">{{ __('Type') }}</th>
                    <th class="cursor-pointer px-3 py-3 text-left font-medium text-slate-600" wire:click="sortBy('licence_plate')">
                        {{ __('Plate') }}
                        @if ($sortField === 'licence_plate')
                            <span class="text-indigo-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-3 py-3 text-left font-medium text-slate-600">{{ __('Category') }}</th>
                    <th class="cursor-pointer px-3 py-3 text-left font-medium text-slate-600" wire:click="sortBy('customer_name')">
                        {{ __('Customer') }}
                        @if ($sortField === 'customer_name')
                            <span class="text-indigo-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-3 py-3 text-left font-medium text-slate-600">{{ __('Phone') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                    <th class="px-3 py-3 text-left font-medium text-slate-600">{{ __('Import date') }}</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-3 text-slate-600">{{ $record->registration_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $record->inspection_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-3 py-3">
                            @if ($record->expiration_date?->isPast())
                                <span class="font-medium text-red-600">{{ $record->expiration_date->format('d/m/Y') }}</span>
                            @elseif ($record->expiration_date && $record->expiration_date->diffInDays(now()) <= 7)
                                <span class="font-medium text-amber-600">{{ $record->expiration_date->format('d/m/Y') }}</span>
                            @else
                                <span class="text-slate-700">{{ $record->expiration_date?->format('d/m/Y') ?? '—' }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-slate-600">{{ $record->vehicle_class ?? '—' }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $record->inspection_type ?? '—' }}</td>
                        <td class="px-3 py-3 font-mono text-xs font-semibold text-slate-800">{{ $record->licence_plate }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $record->vehicle_category ?? '—' }}</td>
                        <td class="px-3 py-3 font-medium">{{ $record->customer_name }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $record->normalized_phone_number }}</td>
                        <td class="px-3 py-3">
                            @php
                                $sc = match($record->status) {
                                    'APTE' => 'bg-green-100 text-green-800',
                                    'INAPTE' => 'bg-red-100 text-red-800',
                                    default => 'bg-amber-100 text-amber-800',
                                };
                            @endphp
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ $record->status }}</span>
                        </td>
                        <td class="px-3 py-3 text-xs text-slate-500">{{ $record->created_at->format('d/m/Y') }}</td>
                        <td class="px-3 py-3 text-right">
                            <a href="{{ route('records.show', $record) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ __('Details') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="px-4 py-8 text-center text-slate-500">
                            {{ __('No record found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
</div>
