<div>
    @if (! $batchId)
        {{-- Upload area --}}
        <div class="space-y-6">
            <div class="flex items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-white px-6 py-12 transition hover:border-indigo-400"
                x-data="{ dragging: false }"
                x-on:dragover.prevent="dragging = true"
                x-on:dragleave="dragging = false"
                x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                :class="{ 'border-indigo-400 bg-indigo-50': dragging }">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="mt-4 text-sm text-slate-600">
                        {{ __('Drag your CSV file here, or') }}
                    </p>
                    <label class="mt-2 inline-block cursor-pointer rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        {{ __('Browse') }}
                        <input type="file" wire:model="file" accept=".csv" class="hidden" x-ref="fileInput" />
                    </label>
                    <p class="mt-2 text-xs text-slate-500">{{ __('CSV with semicolon separator (;), max 10 MB') }}</p>
                </div>
            </div>

            @error('file')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div wire:loading wire:target="file" class="text-sm text-indigo-600">
                {{ __('Uploading file…') }}
            </div>

            @if ($file)
                <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3">
                    <div class="flex items-center gap-3">
                        <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-sm font-medium text-slate-700">{{ $file->getClientOriginalName() }}</span>
                    </div>
                    <button type="button"
                        wire:click="startImport"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                        wire:loading.attr="disabled"
                        wire:target="startImport">
                        <span wire:loading.remove wire:target="startImport">{{ __('Start import') }}</span>
                        <span wire:loading wire:target="startImport">{{ __('Processing…') }}</span>
                    </button>
                </div>
            @endif
        </div>
    @else
        {{-- Progress / Results --}}
        <div wire:poll.2s="checkProgress" class="space-y-6">
            {{-- Status badge --}}
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-5 py-4">
                @if ($status === 'processing')
                    <div class="h-3 w-3 animate-pulse rounded-full bg-amber-400"></div>
                    <span class="text-sm font-medium text-amber-700">{{ __('Processing in progress…') }}</span>
                @elseif ($status === 'completed')
                    <div class="h-3 w-3 rounded-full bg-green-500"></div>
                    <span class="text-sm font-medium text-green-700">{{ $message }}</span>
                @elseif ($status === 'failed')
                    <div class="h-3 w-3 rounded-full bg-red-500"></div>
                    <span class="text-sm font-medium text-red-700">{{ $message }}</span>
                @endif
            </div>

            {{-- Summary cards --}}
            @if (! empty($summary))
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="text-sm text-slate-500">{{ __('Total rows') }}</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($summary['total_rows'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="text-sm text-slate-500">{{ __('Imported') }}</p>
                        <p class="mt-1 text-2xl font-bold text-green-700">{{ number_format($summary['imported_rows'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="text-sm text-slate-500">{{ __('Duplicates') }}</p>
                        <p class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($summary['duplicate_rows'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="text-sm text-slate-500">{{ __('Failed') }}</p>
                        <p class="mt-1 text-2xl font-bold text-red-700">{{ number_format($summary['failed_rows'] ?? 0) }}</p>
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            @if ($status === 'completed' || $status === 'failed')
                <div class="flex gap-3">
                    <button wire:click="resetUpload"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        {{ __('New import') }}
                    </button>
                    @if ($batchId)
                        <a href="{{ route('import-history.show', $batchId) }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            {{ __('View details') }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
