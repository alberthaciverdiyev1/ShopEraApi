<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel' }} — Snaker Manager</title>
    @vite(['resources/admin/app.ts'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased" hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'>
<div id="manager-shell" class="flex min-h-screen">
    @include('manager::partials.sidebar')

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-gray-200 bg-white px-4 py-3">
            <button id="sidebar-toggle" type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
            </button>
            <h1 class="truncate text-base font-semibold text-gray-800">{{ $title ?? 'Panel' }}</h1>

            <div class="ml-auto flex items-center gap-2">
                <div id="global-spinner" class="htmx-indicator h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-brand-600"></div>
                <button type="button" id="user-menu-button" data-dropdown-toggle="user-menu" class="flex items-center rounded-lg p-1.5 hover:bg-gray-100">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">{{ strtoupper(substr(auth('owner')->user()?->name ?? 'M', 0, 1)) }}</span>
                    <span class="mx-2 hidden text-sm font-medium text-gray-700 sm:block">{{ auth('owner')->user()?->name }}</span>
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div id="user-menu" class="z-50 hidden w-52 divide-y divide-gray-100 rounded-lg bg-white shadow">
                    <div class="px-4 py-3 text-sm text-gray-900">{{ auth('owner')->user()?->email }}</div>
                    <div class="py-1">
                        <form method="POST" action="{{ route('manager.logout') }}">
                            @csrf
                            <button class="flex w-full items-center gap-2 px-4 py-2 text-sm text-rose-600 hover:bg-gray-100">Çıxış</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            @include('manager::partials.flash')
            @yield('content')
        </main>
    </div>
</div>

<div id="modal-root"></div>
<div id="toast-root" class="pointer-events-none fixed bottom-5 right-5 z-50 flex flex-col items-end gap-2"></div>
</body>
</html>
