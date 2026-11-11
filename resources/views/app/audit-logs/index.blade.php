@extends('layouts.app')

@section('title', __('Audit logs'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Audit logs') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('Recent security-relevant actions for this center.') }}</p>
        </div>

        <form method="GET" action="{{ route('audit-logs.index') }}" class="flex items-end gap-2">
            <div>
                <label for="action" class="mb-1 block text-xs font-medium text-slate-600">{{ __('Action') }}</label>
                <select id="action" name="action"
                    class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('All actions') }}</option>
                    @foreach ($actions as $option)
                        <option value="{{ $option }}" @selected($action === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                {{ __('Filter') }}
            </button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('When') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('User') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Action') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Description') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('IP address') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                            {{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $log->user?->name ?? '—' }}
                            @if ($log->user?->email)
                                <div class="text-xs text-slate-500">{{ $log->user->email }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $log->action }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $log->description ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No audit logs yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
@endsection
