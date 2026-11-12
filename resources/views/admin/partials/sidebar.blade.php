@php
    $groups = \App\Core\Support\AdminMenu::groups();

    $isActive = fn (string $match) => request()->routeIs($match);

    // Operator-authored disclaimer shown at the very bottom. Optional.
    $disclaimer = null;
    try {
        $disclaimer = trim((string) \Modules\Setting\Entities\Setting::query()->value('admin_disclaimer'));
    } catch (\Throwable) {
        // Settings not available — the sidebar still renders without it.
    }
@endphp

<aside class="sidebar sticky top-0 flex h-screen w-64 shrink-0 flex-col overflow-hidden border-r border-slate-800 bg-slate-900 text-slate-300">
    <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-white/5 px-4">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-lg font-black text-white shadow-lg shadow-indigo-900/40">S</span>
        <span class="brand-text text-lg font-bold tracking-tight text-white">Snaker <span class="font-normal text-slate-500">Admin</span></span>
    </div>

    <div class="menu-search-wrap shrink-0 px-3 pt-3">
        <label class="relative block">
            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input type="search" id="menu-search" placeholder="Menyuda axtar…" autocomplete="off"
                   class="w-full rounded-lg border border-white/10 bg-white/5 py-2 pl-8 pr-3 text-sm text-slate-200 placeholder:text-slate-500 focus:border-indigo-400/60 focus:bg-white/10 focus:outline-none focus:ring-0">
        </label>
    </div>

    <nav id="admin-nav" class="min-h-0 flex-1 space-y-5 overflow-y-auto px-3 py-4">
        @foreach ($groups as $group => $items)
            <div data-menu-group>
                <p class="nav-group-title px-2 pb-1.5 text-[10px] font-semibold uppercase tracking-widest text-slate-500">{{ $group }}</p>
                <ul class="space-y-0.5">
                    @foreach ($items as $item)
                        <li>
                            <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                               data-menu-label="{{ \Illuminate\Support\Str::lower($group.' '.$item['label']) }}"
                               class="nav-link group relative flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition {{ $isActive($item['match'])
                                    ? 'bg-indigo-500/15 text-white'
                                    : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                @if ($isActive($item['match']))
                                    <span class="absolute inset-y-1.5 left-0 w-0.5 rounded-full bg-indigo-400"></span>
                                @endif
                                <svg class="h-5 w-5 shrink-0 {{ $isActive($item['match']) ? 'text-indigo-300' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span class="nav-label truncate">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        <p id="menu-search-empty" class="hidden px-2 py-6 text-center text-xs text-slate-500">Uyğun bölmə tapılmadı.</p>
    </nav>

    <div class="shrink-0 border-t border-white/5 p-3">
        <a href="{{ url('/') }}" target="_blank" rel="noopener"
           class="nav-link flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">
            <svg class="h-5 w-5 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
            <span class="nav-label truncate">Vitrinə bax</span>
        </a>

        @if (! empty($disclaimer))
            <div class="disclaimer mt-2 border-t border-white/5 px-1 pt-3">
                <p class="text-[11px] leading-relaxed text-slate-500">{{ $disclaimer }}</p>
            </div>
        @endif
    </div>
</aside>
