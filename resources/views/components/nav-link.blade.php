@props(['href', 'active' => false])

<a href="{{ $href }}"
    {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors ' . ($active ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900')]) }}>
    {{ $slot }}
</a>
