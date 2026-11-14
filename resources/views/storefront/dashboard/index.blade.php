@php
    $user = auth()->user();
    $initials = collect([$user?->name, $user?->surname])
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('') ?: 'U';

    $tabs = [
        'settings' => ['label' => 'Hesab parametrləri', 'icon' => 'fa-regular fa-gear'],
        'logout' => ['label' => 'Çıxış', 'icon' => 'fa-solid fa-arrow-right-from-bracket'],
    ];
@endphp

<x-storefront.layouts.app :title="'Hesabım — ShopEra'">
    <x-storefront.breadcrumb title="Hesabım" />

    <div class="dashboard-section section-padding fix account-dashboard">
        <div class="container">
            <div class="sf-acc">
                <aside class="sf-acc__side">
                    <div class="sf-acc__card">
                        <div class="sf-acc__profile">
                            <div class="sf-acc__avatar">
                                @if ($user?->avatar)
                                    <img src="{{ $user->avatar }}" alt="{{ $user->name ?? 'Profil' }}">
                                @else
                                    <span>{{ $initials }}</span>
                                @endif
                            </div>
                            <div class="sf-acc__identity">
                                <strong>{{ trim(($user?->name ?? '').' '.($user?->surname ?? '')) ?: 'Hesabım' }}</strong>
                                <span>{{ $user?->email ?? $user?->phone ?? 'Müştəri' }}</span>
                            </div>
                        </div>

                        <nav class="sf-acc__nav" role="tablist" aria-orientation="vertical">
                            @foreach ($tabs as $key => $item)
                                <button type="button" role="tab"
                                        class="sf-acc__nav-item {{ $tab === $key ? 'active' : '' }}"
                                        aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                                        data-sf-dash-tab="{{ $key }}">
                                    <i class="{{ $item['icon'] }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                    <i class="fa-regular fa-chevron-right sf-acc__nav-arrow"></i>
                                </button>
                            @endforeach
                        </nav>

                        <div class="sf-acc__quick">
                            <span class="sf-acc__quick-title">Sürətli keçidlər</span>
                            <a href="{{ route('storefront.orders') }}" class="sf-acc__quick-link">
                                <i class="fa-solid fa-receipt"></i>
                                <span>Sifarişlərim</span>
                            </a>
                            <a href="/wishlist" class="sf-acc__quick-link">
                                <i class="fa-regular fa-heart"></i>
                                <span>İstək siyahısı</span>
                            </a>
                            <a href="/elanlarim" class="sf-acc__quick-link">
                                <i class="fa-solid fa-rectangle-list"></i>
                                <span>Elanlarım</span>
                            </a>
                        </div>
                    </div>
                </aside>

                <div class="sf-acc__main">
                    @if (session('status'))
                        <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="sf-alert sf-alert--error">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="sf-alert sf-alert--error">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="sf-acc__panes">
                        {{-- Settings --}}
                        <section class="sf-dash-pane {{ $tab === 'settings' ? 'is-active' : '' }}" data-sf-dash-pane="settings">
                            <div class="sf-acc__panel">
                                <header class="sf-acc__panel-head">
                                    <h2>Hesab parametrləri</h2>
                                    <p>Profil məlumatlarınızı və şəklinizi buradan yeniləyin.</p>
                                </header>
                                @include('storefront.settings._form', ['user' => $user])
                            </div>
                        </section>

                        {{-- Logout --}}
                        <section class="sf-dash-pane {{ $tab === 'logout' ? 'is-active' : '' }}" data-sf-dash-pane="logout">
                            <div class="sf-acc__panel sf-acc__panel--center">
                                <span class="sf-acc__panel-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                <h2>Çıxış</h2>
                                <p>Hesabınızdan çıxmaq istədiyinizə əminsiniz?</p>
                                <form action="{{ route('storefront.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="sf-btn">Çıxışı təsdiqlə</button>
                                </form>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-storefront.layouts.app>
