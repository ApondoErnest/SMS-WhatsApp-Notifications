@props(['label', 'value', 'color' => 'slate'])

@php
    $colorClasses = match($color) {
        'green' => 'text-green-700',
        'amber' => 'text-amber-700',
        'red' => 'text-red-700',
        default => 'text-slate-900',
    };
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-2xl font-bold {{ $colorClasses }}">{{ $value }}</p>
</div>
