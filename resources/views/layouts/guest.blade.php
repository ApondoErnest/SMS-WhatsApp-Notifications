<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 antialiased">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-indigo-600">{{ config('app.name', 'Visite Notify') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('Technical inspection management') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            @yield('content')
        </div>

        {{-- Language toggle --}}
        <div class="mt-6 flex items-center justify-center gap-2 text-sm">
            <span class="text-slate-400">{{ __('Language') }}:</span>
            <a href="{{ route('locale.switch', 'fr') }}"
                class="rounded px-2 py-1 {{ app()->getLocale() === 'fr' ? 'bg-indigo-100 font-semibold text-indigo-700' : 'text-slate-500 hover:text-slate-800' }}">
                Français
            </a>
            <span class="text-slate-300">|</span>
            <a href="{{ route('locale.switch', 'en') }}"
                class="rounded px-2 py-1 {{ app()->getLocale() === 'en' ? 'bg-indigo-100 font-semibold text-indigo-700' : 'text-slate-500 hover:text-slate-800' }}">
                English
            </a>
        </div>
    </div>
</body>
</html>
