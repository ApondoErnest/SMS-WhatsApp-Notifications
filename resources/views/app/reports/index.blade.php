@extends('layouts.app')

@section('title', __('Reports'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Reports') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('Import, expiry, and notification activity for the selected period.') }}</p>
        </div>
    </div>

    <x-reports-nav :from="$from" :to="$to" />

    <div class="mb-6">
        <x-reports-date-filter :from="$from" :to="$to" :action="route('reports.index')" />
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard-card :label="__('Import batches')" :value="number_format($overview['imports']['batches'])" />
        <x-dashboard-card :label="__('Rows imported')" :value="number_format($overview['imports']['imported_rows'])" color="green" />
        <x-dashboard-card :label="__('Failed import rows')" :value="number_format($overview['imports']['failed_rows'])" color="red" />
        <x-dashboard-card :label="__('Failed notifications')" :value="number_format($overview['failed_notifications'])" color="red" />
    </div>

    <div class="mb-8 grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Expiry') }}</h2>
                <a href="{{ route('reports.expiry', ['window' => 'month']) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ __('View') }} →</a>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('This week') }}</dt><dd class="font-semibold text-amber-700">{{ number_format($overview['expiry']['this_week']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('This month') }}</dt><dd class="font-semibold text-amber-700">{{ number_format($overview['expiry']['this_month']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('In selected range') }}</dt><dd class="font-semibold text-slate-900">{{ number_format($overview['expiry']['in_range']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Already expired') }}</dt><dd class="font-semibold text-red-700">{{ number_format($overview['expiry']['expired']) }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('SMS delivery') }}</h2>
                <a href="{{ route('reports.notifications', ['from' => $from, 'to' => $to, 'channel' => 'sms']) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ __('View') }} →</a>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Sent') }}</dt><dd class="font-semibold">{{ number_format($overview['sms']['sent']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Delivered') }}</dt><dd class="font-semibold text-green-700">{{ number_format($overview['sms']['delivered']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Failed') }}</dt><dd class="font-semibold text-red-700">{{ number_format($overview['sms']['failed']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Skipped') }}</dt><dd class="font-semibold text-slate-600">{{ number_format($overview['sms']['skipped']) }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('WhatsApp delivery') }}</h2>
                <a href="{{ route('reports.notifications', ['from' => $from, 'to' => $to, 'channel' => 'whatsapp']) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ __('View') }} →</a>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Sent') }}</dt><dd class="font-semibold">{{ number_format($overview['whatsapp']['sent']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Delivered') }}</dt><dd class="font-semibold text-green-700">{{ number_format($overview['whatsapp']['delivered']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Read') }}</dt><dd class="font-semibold text-green-700">{{ number_format($overview['whatsapp']['read']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Failed') }}</dt><dd class="font-semibold text-red-700">{{ number_format($overview['whatsapp']['failed']) }}</dd></div>
            </dl>
        </div>
    </div>

    <div>
        <h2 class="mb-3 text-lg font-semibold text-slate-900">{{ __('Daily activity') }}</h2>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Imported') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('SMS sent') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('WhatsApp sent') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Failed') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($overview['daily'] as $day)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($day['imported_rows']) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($day['sms_sent']) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($day['whatsapp_sent']) }}</td>
                            <td class="px-4 py-3 text-right {{ $day['failed'] > 0 ? 'text-red-700 font-medium' : '' }}">{{ number_format($day['failed']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">{{ __('No activity in this period.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
