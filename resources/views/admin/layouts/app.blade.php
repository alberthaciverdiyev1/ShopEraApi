@php
    $crumb = \App\Core\Support\AdminMenu::breadcrumb(request()->route()?->getName());
    $admin = admin_user();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel' }} — Snaker Admin</title>
    <script>
        // Apply the saved theme before paint so the panel never flashes.
        (function () {
            try {
                var saved = localStorage.getItem('admin:theme');
                var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {}
        })();
    </script>
    @vite(['resources/js/admin.ts'])
</head>
<body class="bg-slate-100 text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-100"
      hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'>
<div id="admin-shell" class="flex min-h-screen">

    @include('admin.partials.sidebar')

    {{-- Mobile drawer backdrop --}}
    <div class="sidebar-backdrop" data-drawer-close></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-slate-200 bg-white/95 px-4 py-2.5 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
            <button id="sidebar-toggle" type="button" data-drawer-toggle
                    class="inline-flex items-center rounded-lg p-2 text-sm text-slate-500 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 dark:text-slate-400 dark:hover:bg-slate-800"
                    title="Menyu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
            </button>

            <div class="min-w-0">
                @if ($crumb)
                    <p class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">{{ $crumb[0] }}</a>
                        <span aria-hidden="true">/</span>
                        <span class="truncate">{{ $crumb[1] }}</span>
                    </p>
                @endif
                <h1 class="truncate text-[15px] font-semibold leading-tight text-slate-800 dark:text-white">{{ $title ?? 'Panel' }}</h1>
            </div>

            <div class="ml-auto flex items-center gap-1.5">
                <div id="global-spinner" class="htmx-indicator mr-1 h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-indigo-600 dark:border-slate-700"></div>

                <a href="{{ route('admin.notifications.index') }}" title="Bildirişlər"
                   class="hidden rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 sm:inline-flex">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                </a>

                <button type="button" id="theme-toggle" title="İşıq / qaranlıq"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg data-theme-icon="light" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z"/></svg>
                    <svg data-theme-icon="dark" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998z"/></svg>
                </button>

                <span class="mx-1 hidden h-6 w-px bg-slate-200 dark:bg-slate-700 sm:block"></span>

                <button type="button" id="user-menu-button" data-dropdown-toggle="user-menu"
                        class="flex items-center gap-2 rounded-lg p-1.5 text-sm hover:bg-slate-100 dark:hover:bg-slate-800">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-sm font-semibold text-white">
                        {{ strtoupper(substr($admin?->name ?? 'A', 0, 1)) }}
                    </span>
                    <span class="mx-1 hidden text-left sm:block">
                        <span class="block text-[13px] font-semibold leading-tight text-slate-800 dark:text-white">{{ $admin?->name }} {{ $admin?->surname }}</span>
                        <span class="block max-w-40 truncate text-[11px] leading-tight text-slate-500 dark:text-slate-400">{{ $admin?->email ?? $admin?->phone }}</span>
                    </span>
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>

                <div id="user-menu" class="z-50 hidden w-60 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-xl dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center gap-3 px-4 py-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-sm font-semibold text-white">
                            {{ strtoupper(substr($admin?->name ?? 'A', 0, 1)) }}
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-800 dark:text-white">{{ $admin?->name }} {{ $admin?->surname }}</span>
                            <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $admin?->phone ?? $admin?->email }}</span>
                        </span>
                    </div>
                    <div class="py-1">
                        <a href="{{ route('admin.profile.password') }}"
                           class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                            Şifrəni dəyiş
                        </a>
                        <a href="{{ url('/') }}" target="_blank" rel="noopener"
                           class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                            Mağazaya bax
                        </a>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="flex w-full items-center gap-2 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-slate-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3-3H9m0 0 3-3m-3 3 3 3"/></svg>
                                Çıxış
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-5 sm:px-6 sm:py-6">
            <div class="mx-auto w-full max-w-[1600px]">
                @include('admin.partials.flash')

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
