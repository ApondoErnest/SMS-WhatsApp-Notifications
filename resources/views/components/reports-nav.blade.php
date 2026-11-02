@php
    $query = array_filter([
        'from' => $from ?? request('from'),
        'to' => $to ?? request('to'),
    ]);
@endphp

<div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
    <a href="{{ route('reports.index', $query) }}"
        class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('reports.index') ? 'bg-indigo-100 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
        {{ __('Overview') }}
    </a>
    <a href="{{ route('reports.imports', $query) }}"
        class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('reports.imports') ? 'bg-indigo-100 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
        {{ __('Imports') }}
    </a>
    <a href="{{ route('reports.expiry', ['window' => 'month']) }}"
        class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('reports.expiry') ? 'bg-indigo-100 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
        {{ __('Expiry') }}
    </a>
    <a href="{{ route('reports.notifications', $query) }}"
        class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('reports.notifications') ? 'bg-indigo-100 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
        {{ __('Notifications') }}
    </a>
</div>
