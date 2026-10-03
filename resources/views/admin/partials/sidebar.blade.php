@php
    $groups = \App\Support\AdminMenu::groups();

    $isActive = fn (string $match) => request()->routeIs($match);
@endphp

<aside class="sidebar sticky top-0 h-screen w-64 shrink-0 overflow-y-auto border-r border-gray-200 bg-white transition-all duration-200 dark:border-gray-700 dark:bg-gray-800">
    <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-4 dark:border-gray-700">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-lg font-black text-white">S</span>
        <span class="brand-text text-lg font-bold text-gray-800 dark:text-white">Snaker <span class="font-normal text-gray-400">Admin</span></span>
    </div>

    <nav class="space-y-4 px-3 py-4">
        @foreach ($groups as $group => $items)
            <div>
                <p class="nav-group-title px-2 pb-1 text-xs font-semibold uppercase text-gray-400 dark:text-gray-500">{{ $group }}</p>
                <ul class="space-y-0.5">
                    @foreach ($items as $item)
                        @php $locked = ! \App\Support\AdminMenu::unlocked($item); @endphp
                        <li>
                            @if ($locked)
                                {{-- Abunəliyə daxil deyil: klikləyəndə planı yüksəlt səhifəsinə keçir. --}}
                                <a href="{{ route($item['route']) }}"
                                   title="{{ $item['label'] }} — Premium plan lazımdır (yalnız baxış)"
                                   data-locked-feature="{{ $item['feature'] ?? '' }}"
                                   class="nav-link flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-gray-400 opacity-70 transition hover:bg-amber-50 hover:opacity-100 dark:text-gray-500 dark:hover:bg-amber-500/10">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                    </svg>
                                    <span class="nav-label truncate">{{ $item['label'] }}</span>
                                    <span class="ml-auto shrink-0 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">Premium</span>
                                </a>
                            @else
                                <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                                   class="nav-link flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition {{ $isActive($item['match'])
                                        ? 'bg-brand-600 text-white'
                                        : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                    </svg>
                                    <span class="nav-label truncate">{{ $item['label'] }}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-gray-200 p-3 dark:border-gray-700">
        <a href="{{ url('/') }}" target="_blank"
           class="nav-link flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
            <span class="nav-label truncate">Vitrin</span>
        </a>
    </div>
</aside>
