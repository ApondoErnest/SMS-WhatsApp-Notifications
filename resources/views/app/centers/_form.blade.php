@php
    $center = $center ?? null;
@endphp

<div>
    <label for="name" class="block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
    <input type="text" name="name" id="name" value="{{ old('name', $center->name ?? '') }}" required
        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <label for="code" class="block text-sm font-medium text-slate-700">{{ __('Code') }} <span class="text-slate-400">({{ __('optional') }})</span></label>
        <input type="text" name="code" id="code" value="{{ old('code', $center->code ?? '') }}" maxlength="20"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
        <p class="mt-1 text-xs text-slate-500">{{ __('Letters, numbers and dashes only, e.g. DLA') }}</p>
        @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="city" class="block text-sm font-medium text-slate-700">{{ __('City') }}</label>
        <input type="text" name="city" id="city" value="{{ old('city', $center->city ?? '') }}"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
        @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <label for="phone" class="block text-sm font-medium text-slate-700">{{ __('Phone') }}</label>
        <input type="text" name="phone" id="phone" value="{{ old('phone', $center->phone ?? '') }}"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-slate-700">{{ __('Email address') }}</label>
        <input type="email" name="email" id="email" value="{{ old('email', $center->email ?? '') }}"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label for="address" class="block text-sm font-medium text-slate-700">{{ __('Address') }}</label>
    <textarea name="address" id="address" rows="3"
        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">{{ old('address', $center->address ?? '') }}</textarea>
    @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

@if ($showStatus ?? true)
<div>
    <label for="status" class="block text-sm font-medium text-slate-700">{{ __('Status') }}</label>
    <select name="status" id="status" required
        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
        <option value="active" @selected(old('status', $center->status ?? 'active') === 'active')>{{ __('Active') }}</option>
        <option value="inactive" @selected(old('status', $center->status ?? '') === 'inactive')>{{ __('Inactive') }}</option>
    </select>
    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
@endif
