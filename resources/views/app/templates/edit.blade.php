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
                {{ __('Available variables:') }}
                <code class="text-indigo-600">{customer_name}</code>,
                <code class="text-indigo-600">{licence_plate}</code>,
                <code class="text-indigo-600">{expiration_date}</code>,
                <code class="text-indigo-600">{center_name}</code>
            </p>
            @error('content')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        @if ($template->channel === 'whatsapp')
            <div class="grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <p class="text-sm font-medium text-slate-800">{{ __('Meta WhatsApp template') }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Must match an approved template in Meta Business Manager. Named variables: customer_name, licence_plate, expiration_date, center_name.') }}</p>
                </div>
                <div>
                    <label for="meta_template_name" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Meta template name') }}</label>
                    <input id="meta_template_name" name="meta_template_name" type="text"
                        value="{{ old('meta_template_name', $template->meta_template_name) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        placeholder="visite_expiry_reminder">
                    @error('meta_template_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="meta_language_code" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Meta language code') }}</label>
                    <input id="meta_language_code" name="meta_language_code" type="text"
                        value="{{ old('meta_language_code', $template->meta_language_code ?? $template->language) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        placeholder="fr">
                    @error('meta_language_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        <div>
            <label for="status" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Status') }}</label>
            <select id="status" name="status" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option value="active" {{ old('status', $template->status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                <option value="inactive" {{ old('status', $template->status) === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
            </select>
        </div>

        {{-- Live preview --}}
        @php
            $previewCenterName = app(\App\Support\CurrentCenter::class)->center()?->name ?: 'Centre de Visite Technique Douala';
            $previewReplacements = [
                '{customer_name}' => 'Jean Dupont',
                '{licence_plate}' => 'LT1234AB',
                '{expiration_date}' => '15/12/2026',
                '{center_name}' => $previewCenterName,
            ];
        @endphp
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="mb-2 text-xs font-medium text-slate-500">{{ __('Preview (with sample data):') }}</p>
            <p class="whitespace-pre-wrap text-sm text-slate-700" id="preview-text">{{ strtr($template->content, $previewReplacements) }}</p>
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
    const previewSamples = {
        customer_name: @json('Jean Dupont'),
        licence_plate: @json('LT1234AB'),
        expiration_date: @json('15/12/2026'),
        center_name: @json($previewCenterName),
    };

    document.getElementById('content').addEventListener('input', function () {
        let text = this.value;
        Object.entries(previewSamples).forEach(([key, value]) => {
            text = text.replaceAll('{' + key + '}', value);
        });
        document.getElementById('preview-text').textContent = text;
    });
</script>
@endpush
