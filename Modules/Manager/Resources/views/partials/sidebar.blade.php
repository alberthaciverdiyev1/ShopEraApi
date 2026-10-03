@php
    $groups = [
        'İdarə' => [
            ['label' => 'İcmal', 'route' => 'manager.dashboard', 'match' => 'manager.dashboard', 'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
        ],
        'Müştərilər' => [
            ['label' => 'Sahiblər', 'route' => 'manager.owners.index', 'match' => 'manager.owners.*', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        ],
        'Abunəlik' => [
            ['label' => 'Planlar', 'route' => 'manager.plans.index', 'match' => 'manager.plans.*', 'icon' => 'M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z'],
            ['label' => 'Feature-lar', 'route' => 'manager.features.index', 'match' => 'manager.features.*', 'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
            ['label' => 'Promo bloklar', 'route' => 'manager.promo-blocks.index', 'match' => 'manager.promo-blocks.*', 'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6zM7.5 8.25h9M7.5 12h9M7.5 15.75h5.25'],
        ],
        'Sistem' => [
            ['label' => 'Temalar', 'route' => 'manager.themes.index', 'match' => 'manager.themes.*', 'icon' => 'M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88'],
            ['label' => 'Parametrlər', 'route' => 'manager.settings.index', 'match' => 'manager.settings.*', 'icon' => 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z'],
        ],
    ];
    $isActive = fn (string $match) => request()->routeIs($match);
@endphp

<aside class="sidebar sticky top-0 h-screen w-64 shrink-0 overflow-y-auto border-r border-gray-200 bg-white transition-all duration-200">
    <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-4">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-lg font-black text-white">M</span>
        <span class="brand-text text-lg font-bold text-gray-800">Snaker <span class="font-normal text-gray-400">Manager</span></span>
    </div>

    <nav class="space-y-4 px-3 py-4">
        @foreach ($groups as $group => $items)
            <div>
                <p class="nav-group-title px-2 pb-1 text-xs font-semibold uppercase text-gray-400">{{ $group }}</p>
                <ul class="space-y-0.5">
                    @foreach ($items as $item)
                        <li>
                            <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                               class="nav-link flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition {{ $isActive($item['match']) ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                                <span class="nav-label truncate">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</aside>
