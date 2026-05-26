<div class="space-y-4">
    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <input type="text" wire:model.live.debounce.300ms="search"
            placeholder="{{ __('Search by file…') }}"
            class="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <select wire:model.live="statusFilter"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <option value="">{{ __('All statuses') }}</option>
            <option value="completed">{{ __('Completed') }}</option>
            <option value="processing">{{ __('Processing') }}</option>
            <option value="failed">{{ __('Failed') }}</option>
            <option value="pending">{{ __('Pending') }}</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('File') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Imported by') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Total') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Imported') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Duplicates') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Failed') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($batches as $batch)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $batch->original_filename }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $batch->uploader?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($batch->total_rows) }}</td>
                        <td class="px-4 py-3 text-right text-green-700">{{ number_format($batch->imported_rows) }}</td>
                        <td class="px-4 py-3 text-right text-amber-700">{{ number_format($batch->duplicate_rows) }}</td>
                        <td class="px-4 py-3 text-right text-red-700">{{ number_format($batch->failed_rows) }}</td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = ['completed' => 'bg-green-100 text-green-800', 'processing' => 'bg-amber-100 text-amber-800', 'failed' => 'bg-red-100 text-red-800', 'pending' => 'bg-slate-100 text-slate-700'];
                            @endphp
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$batch->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ __(ucfirst($batch->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('import-history.show', $batch) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('Details') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-slate-500">
                            {{ __('No import found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $batches->links() }}</div>
</div>
