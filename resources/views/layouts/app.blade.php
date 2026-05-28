<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-30 w-64 transform border-r border-slate-200 bg-white transition-transform duration-200 lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 items-center border-b border-slate-200 px-6">
                <a href="{{ route('dashboard') }}" class="text-lg font-bold text-indigo-600">
                    {{ config('app.name', 'Visite Notify') }}
                </a>
            </div>

            <nav class="mt-4 space-y-1 px-3">
                <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                    <x-icon-dashboard class="h-5 w-5" />
                    {{ __('Dashboard') }}
                </x-nav-link>

                @can('import-csv')
                <x-nav-link href="{{ route('imports.create') }}" :active="request()->routeIs('imports.*')">
                    <x-icon-upload class="h-5 w-5" />
                    {{ __('Import CSV') }}
                </x-nav-link>
                @endcan

                <x-nav-link href="{{ route('import-history.index') }}" :active="request()->routeIs('import-history.*')">
                    <x-icon-clock class="h-5 w-5" />
                    {{ __('Import history') }}
                </x-nav-link>

                @can('view-records')
                <x-nav-link href="{{ route('records.index') }}" :active="request()->routeIs('records.*')">
                    <x-icon-table class="h-5 w-5" />
                    {{ __('Records') }}
                </x-nav-link>
                @endcan

                <x-nav-link href="{{ route('schedules.index') }}" :active="request()->routeIs('schedules.*')">
                    <x-icon-bell class="h-5 w-5" />
                    {{ __('Schedules') }}
                </x-nav-link>

                <x-nav-link href="{{ route('notifications.index') }}" :active="request()->routeIs('notifications.*')">
                    <x-icon-message class="h-5 w-5" />
                    {{ __('Notifications') }}
                </x-nav-link>

                @can('manage-templates')
                <x-nav-link href="{{ route('templates.index') }}" :active="request()->routeIs('templates.*')">
                    <x-icon-document class="h-5 w-5" />
                    {{ __('Templates') }}
                </x-nav-link>
                @endcan

                @can('manage-users')
                <x-nav-link href="{{ route('users.index') }}" :active="request()->routeIs('users.*')">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                    {{ __('Users') }}
                </x-nav-link>
                @endcan
            </nav>

            {{-- Language switcher + user --}}
            <div class="absolute bottom-0 w-full border-t border-slate-200 p-4">
                <div class="mb-3 flex items-center justify-center gap-1 text-xs">
                    <a href="{{ route('locale.switch', 'fr') }}"
                        class="rounded px-2 py-1 {{ app()->getLocale() === 'fr' ? 'bg-indigo-100 font-semibold text-indigo-700' : 'text-slate-500 hover:text-slate-800' }}">
                        FR
                    </a>
                    <span class="text-slate-300">|</span>
                    <a href="{{ route('locale.switch', 'en') }}"
                        class="rounded px-2 py-1 {{ app()->getLocale() === 'en' ? 'bg-indigo-100 font-semibold text-indigo-700' : 'text-slate-500 hover:text-slate-800' }}">
                        EN
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-600">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-1 truncate">
                        <p class="truncate text-sm font-medium text-slate-800">{{ auth()->user()->name ?? '' }}</p>
                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->role ?? 'operator' }}</p>
                    </div>
                </div>
                <a href="{{ route('account.password.edit') }}"
                    class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    {{ __('Change password') }}
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="w-full rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-600 hover:bg-slate-200">
                        {{ __('Logout') }}
                    </button>
                </form>
            </div>
        </aside>

        {{-- Overlay for mobile --}}
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
            class="fixed inset-0 z-20 bg-black/30 lg:hidden"></div>

        {{-- Main content --}}
        <div class="flex flex-1 flex-col">
            <header class="flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-6 lg:hidden">
                <button @click="sidebarOpen = true" class="text-slate-600 hover:text-slate-900">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="text-lg font-semibold text-slate-800">{{ config('app.name') }}</span>
            </header>

            <main class="flex-1 p-6">
                @if (session('status'))
                    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
