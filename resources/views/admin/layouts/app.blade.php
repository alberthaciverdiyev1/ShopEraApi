<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel' }} — Snaker Admin</title>
    @vite(['resources/admin/app.ts'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100"
      hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'>
<div id="admin-shell" class="flex min-h-screen">

    @include('admin.partials.sidebar')

    {{-- Mobile drawer backdrop --}}
    <div class="sidebar-backdrop" data-drawer-close></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
            <button id="sidebar-toggle" type="button" data-drawer-toggle
                    class="inline-flex items-center rounded-lg p-2 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700"
                    title="Menyu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
            </button>

            <h1 class="truncate text-base font-semibold text-gray-800 dark:text-white">{{ $title ?? 'Panel' }}</h1>

            <div class="ml-auto flex items-center gap-2">
                <div id="global-spinner" class="htmx-indicator h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-brand-600"></div>

                <button type="button" id="user-menu-button" data-dropdown-toggle="user-menu"
                        class="flex items-center rounded-lg p-1.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">
                        {{ strtoupper(substr(admin_user()?->name ?? 'A', 0, 1)) }}
                    </span>
                    <span class="mx-2 hidden text-left sm:block">
                        <span class="block text-sm font-medium text-gray-800 dark:text-white">{{ admin_user()?->name }} {{ admin_user()?->surname }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ admin_user()?->email ?? admin_user()?->phone }}</span>
                    </span>
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>

                <div id="user-menu" class="z-50 hidden w-56 divide-y divide-gray-100 rounded-lg bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                    <div class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                        {{ admin_user()?->phone ?? admin_user()?->email }}
                    </div>
                    <div class="py-1">
                        <a href="{{ route('admin.profile.password') }}"
                           class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                            Şifrəni dəyiş
                        </a>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="flex w-full items-center gap-2 px-4 py-2 text-sm text-rose-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3-3H9m0 0 3-3m-3 3 3 3"/></svg>
                                Çıxış
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            @include('admin.partials.flash')

            <div>
                @yield('content')
            </div>
        </main>
    </div>
</div>

{{-- htmx modal host --}}
<div id="modal-root"></div>

<div id="toast-root" class="pointer-events-none fixed bottom-5 right-5 z-50 flex flex-col items-end gap-2"></div>
</body>
</html>
