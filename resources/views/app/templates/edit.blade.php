@extends('layouts.app')

@section('title', __('Edit template'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('templates.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← {{ __('Back to templates') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ __('Edit template') }}</h1>
        <p class="mt-1 text-sm text-slate-600">
            @if ($template->channel === 'sms')
                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">SMS</span>
            @else
                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">WhatsApp</span>
            @endif
            <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ strtoupper($template->language) }}</span>
        </p>
    </div>

    <form method="POST" action="{{ route('templates.update', $template) }}" class="max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="title" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Title') }}</label>
            <input id="title" name="title" type="text" value="{{ old('title', $template->title) }}" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            @error('title')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="content" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Message content') }}</label>
            <textarea id="content" name="content" rows="5" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('content', $template->content) }}</textarea>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('Available variables:') }} <code class="text-indigo-600">{customer_name}</code>, <code class="text-indigo-600">{licence_plate}</code>, <code class="text-indigo-600">{expiration_date}</code>
            </p>
            @error('content')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="status" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Status') }}</label>
            <select id="status" name="status" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option value="active" {{ old('status', $template->status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                <option value="inactive" {{ old('status', $template->status) === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
            </select>
        </div>

        {{-- Live preview --}}
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="mb-2 text-xs font-medium text-slate-500">{{ __('Preview (with sample data):') }}</p>
            <p class="whitespace-pre-wrap text-sm text-slate-700" id="preview-text">{{ str_replace(['{customer_name}', '{licence_plate}', '{expiration_date}'], ['Jean Dupont', 'LT 1234 AB', '15/12/2026'], $template->content) }}</p>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ __('Save changes') }}
            </button>
            <a href="{{ route('templates.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.getElementById('content').addEventListener('input', function() {
        let text = this.value
            .replace(/\{customer_name\}/g, 'Jean Dupont')
            .replace(/\{licence_plate\}/g, 'LT 1234 AB')
            .replace(/\{expiration_date\}/g, '15/12/2026');
        document.getElementById('preview-text').textContent = text;
    });
</script>
@endpush
