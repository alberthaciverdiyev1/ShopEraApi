@php
    $localized = function ($value): string {
        if (is_array($value)) {
            return $value['az'] ?? $value['en'] ?? (string) reset($value);
        }

        return (string) ($value ?? '');
    };

    $storeName = $localized($payload['name'] ?? '');
    $storeSlug = $payload['slug'] ?? \Illuminate\Support\Str::slug($storeName ?: 'magaza');
    $storeDescription = $localized($payload['description'] ?? '');
    $initial = mb_strtoupper(mb_substr($storeName ?: 'M', 0, 1));
    $isOwner = ! empty($payload['is_owner']);
    $permissions = $payload['permissions'] ?? [];
    $can = fn (string $key): bool => in_array($key, $permissions, true);
    $canSettings = $can('settings');
    $canListings = $can('listings');

    $stats = [
        'total' => count($listings),
        'promoted' => collect($listings)->where('is_promoted', true)->count(),
        'premium' => collect($listings)->where('is_premium', true)->count(),
        'views' => collect($listings)->sum('views'),
        'contacts' => collect($listings)->sum('contact_reveals'),
    ];

    $activeListings = ($tab === 'promoted')
        ? collect($listings)->filter(fn ($l) => ! empty($l['is_promoted']) || ! empty($l['is_premium']))->all()
        : $listings;

    $toPhoto = function (array $listing) use ($localized): array {
        $images = $listing['images'] ?? [];
        $images = $images instanceof \Illuminate\Support\Collection ? $images->all() : (array) $images;
        $firstImage = null;
        if (! empty($images)) {
            $first = $images[0];
            $firstImage = is_array($first) ? ($first['image_path'] ?? reset($first)) : $first;
        }

        return [
            'id' => $listing['id'] ?? null,
            'title' => $localized($listing['title'] ?? ''),
            'slug' => $listing['slug'] ?? null,
            'price' => $listing['price'] ?? 0,
            'city' => $localized($listing['city'] ?? '') ?: null,
            'created_at' => $listing['created_at'] ?? null,
            'is_premium' => (bool) ($listing['is_premium'] ?? false),
            'is_promoted' => (bool) ($listing['is_promoted'] ?? false),
            'views' => (int) ($listing['views'] ?? 0),
            'contacts' => (int) ($listing['contact_reveals'] ?? 0),
            'image' => $firstImage,
            'image_count' => count($images),
        ];
    };
@endphp

<x-storefront.layouts.app :title="$storeName.' — Mağaza paneli'">
    <div class="sf-ig-page">
        <div class="sf-ig-card">
            {{-- Instagram Top Bar --}}
            <div class="sf-ig-topbar">
                <a href="{{ route('storefront.dashboard') }}" class="sf-ig-topbar__btn" aria-label="Hesabım">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </a>
                <div class="sf-ig-topbar__title">
                    <span>{{ $storeSlug }}</span>
                    <svg class="sf-ig-verified" width="16" height="16" viewBox="0 0 24 24" fill="#16a34a">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <div class="sf-ig-topbar__menu">
                    <button type="button" class="sf-ig-topbar__btn" id="sfIgTopMenuToggle" aria-label="Daha çox">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="5" cy="12" r="2"></circle>
                            <circle cx="12" cy="12" r="2"></circle>
                            <circle cx="19" cy="12" r="2"></circle>
                        </svg>
                    </button>
                    <div class="sf-ig-dropdown-menu" id="sfIgTopDropdown">
                        <a href="/magaza/{{ $payload['slug'] ?? '' }}" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Mağaza səhifəsi</a>
                        @if ($canListings)
                            <a href="/elanlarim"><i class="fa-solid fa-rectangle-list"></i> Elanlarım</a>
                        @endif
                        <a href="/magaza/komanda"><i class="fa-solid fa-users"></i> {{ $isOwner ? 'Komanda' : 'İcazələrim' }}</a>
                        @if ($canSettings)
                            <a href="{{ route('storefront.store.panel', ['tab' => 'settings']) }}"><i class="fa-solid fa-gear"></i> Parametrlər</a>
                        @endif
                        @if ($canListings)
                            <a href="/elan/ver"><i class="fa-solid fa-plus"></i> Yeni elan</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Profile Header (Avatar + 3 Stats) --}}
            <header class="sf-ig__header">
                <div class="sf-ig__avatar-wrap">
                    <div class="sf-ig__story-ring">
                        <div class="sf-ig__avatar">
                            @if (! empty($payload['logo_url']))
                                <img src="{{ $payload['logo_url'] }}" alt="{{ $storeName }}">
                            @else
                                <span>{{ $initial }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="sf-ig__stats-row">
                    <div class="sf-ig__stat">
                        <span class="sf-ig__stat-num">{{ number_format($stats['total']) }}</span>
                        <span class="sf-ig__stat-lbl">Elan</span>
                    </div>
                    <div class="sf-ig__stat">
                        <span class="sf-ig__stat-num">{{ number_format($stats['views']) }}</span>
                        <span class="sf-ig__stat-lbl">Baxış</span>
                    </div>
                    <div class="sf-ig__stat">
                        <span class="sf-ig__stat-num">{{ number_format($stats['contacts']) }}</span>
                        <span class="sf-ig__stat-lbl">Əlaqə</span>
                    </div>
                </div>
            </header>

            {{-- Bio / Info Section --}}
            <div class="sf-ig__bio-section">
                <div class="sf-ig__title-row">
                    <h1 class="sf-ig__name">{{ $storeName !== '' ? $storeName : 'Mağazam' }}</h1>
                    <span class="sf-badge">{{ $isOwner ? 'Sahib' : 'Komanda üzvü' }}</span>
                </div>

                @if ($storeDescription !== '')
                    <p class="sf-ig__bio">{{ $storeDescription }}</p>
                @endif

                <div class="sf-ig__meta-info">
                    @if (! empty($payload['address']))
                        <span><i class="fa-solid fa-location-dot"></i> {{ $payload['address'] }}</span>
                    @endif
                    @if (! empty($payload['phone']))
                        <span><i class="fa-solid fa-phone"></i> {{ $payload['phone'] }}</span>
                    @endif
                </div>

                @if (! empty($payload['slug']))
                    <a class="sf-ig__website" href="/magaza/{{ $payload['slug'] }}" target="_blank" rel="noopener">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                        {{ preg_replace('#^https?://#', '', url('/magaza/'.$payload['slug'])) }}
                    </a>
                @endif
            </div>

            {{-- Action Buttons Row --}}
            <div class="sf-ig__actions-row">
                @if ($canListings)
                    <a class="sf-ig-btn sf-ig-btn--primary" href="/elan/ver">
                        <span>Yeni elan</span>
                        <i class="fa-solid fa-plus ms-1"></i>
                    </a>
                @endif

                @if ($canSettings)
                    <a class="sf-ig-btn" href="{{ route('storefront.store.panel', ['tab' => $tab === 'settings' ? 'listings' : 'settings']) }}">
                        <span>{{ $tab === 'settings' ? 'Elanlar' : 'Redaktə et' }}</span>
                    </a>
                @endif

                <a class="sf-ig-btn" href="/magaza/{{ $payload['slug'] ?? '' }}" target="_blank" rel="noopener">
                    <span>Mağaza</span>
                </a>

                <div class="sf-ig-dropdown-container">
                    <button type="button" class="sf-ig-btn sf-ig-btn--icon" id="sfIgActionMoreBtn" aria-label="Menyu">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="sf-ig-dropdown-menu sf-ig-dropdown-menu--right" id="sfIgActionDropdown">
                        @if ($canListings)
                            <a href="/elanlarim"><i class="fa-solid fa-rectangle-list"></i> Elanları idarə et</a>
                        @endif
                        <a href="/magaza/komanda"><i class="fa-solid fa-users"></i> {{ $isOwner ? 'Komanda idarəetməsi' : 'İcazələrim' }}</a>
                        @if ($canSettings)
                            <a href="{{ route('storefront.store.panel', ['tab' => 'settings']) }}"><i class="fa-solid fa-gear"></i> Parametrlər</a>
                        @endif
                        @if ($canListings)
                            <a href="/elan/ver"><i class="fa-solid fa-plus"></i> Yeni elan</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Story Highlights --}}
            <div class="sf-ig__highlights">
                @if ($canListings)
                    <a href="/elan/ver" class="sf-ig__highlight-item">
                        <div class="sf-ig__highlight-circle sf-ig__highlight-circle--new">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <span>Yeni elan</span>
                    </a>
                @endif
                <a href="/magaza/{{ $payload['slug'] ?? '' }}" target="_blank" class="sf-ig__highlight-item">
                    <div class="sf-ig__highlight-circle">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <span>Mağaza</span>
                </a>
                <a href="/magaza/komanda" class="sf-ig__highlight-item">
                    <div class="sf-ig__highlight-circle">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span>Komanda</span>
                </a>
                @if ($canSettings)
                    <a href="{{ route('storefront.store.panel', ['tab' => 'settings']) }}" class="sf-ig__highlight-item">
                        <div class="sf-ig__highlight-circle">
                            <i class="fa-solid fa-gear"></i>
                        </div>
                        <span>Tənzimləmə</span>
                    </a>
                @endif
                @if ($canListings)
                    <a href="/elanlarim" class="sf-ig__highlight-item">
                        <div class="sf-ig__highlight-circle">
                            <i class="fa-solid fa-rectangle-list"></i>
                        </div>
                        <span>Elanlarım</span>
                    </a>
                @endif
            </div>

            {{-- Tabs Row (4 Icons) --}}
            <nav class="sf-ig__tabs" aria-label="Bölmələr">
                @if ($canListings)
                    <a href="{{ route('storefront.store.panel', ['tab' => 'listings']) }}"
                       class="{{ in_array($tab, ['listings', 'overview'], true) ? 'is-active' : '' }}" title="Elanlar">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="1"></rect>
                            <line x1="9" y1="3" x2="9" y2="21"></line>
                            <line x1="15" y1="3" x2="15" y2="21"></line>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="3" y1="15" x2="21" y2="15"></line>
                        </svg>
                    </a>
                    <a href="{{ route('storefront.store.panel', ['tab' => 'promoted']) }}"
                       class="{{ $tab === 'promoted' ? 'is-active' : '' }}" title="İrəli çəkilmiş">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="4" ry="4"></rect>
                            <polygon points="10 8 16 12 10 16 10 8" fill="currentColor"></polygon>
                            <line x1="2" y1="7" x2="22" y2="7"></line>
                        </svg>
                    </a>
                @endif
                @if ($canSettings)
                    <a href="{{ route('storefront.store.panel', ['tab' => 'settings']) }}"
                       class="{{ $tab === 'settings' ? 'is-active' : '' }}" title="Mağaza məlumatları">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect>
                            <polyline points="17 2 12 7 7 2"></polyline>
                        </svg>
                    </a>
                @endif
                <a href="/magaza/komanda" title="Komanda">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                        <rect x="3" y="2" width="18" height="20" rx="2" ry="2"></rect>
                    </svg>
                </a>
            </nav>

            {{-- Flash status --}}
            @if (session('status'))
                <div class="sf-alert sf-alert--success mx-3 mt-3">{{ session('status') }}</div>
            @endif

            {{-- Main Content Panels --}}
            @if (! $canListings && ! $canSettings)
                <div class="sf-panel sf-ig__notice">
                    <h6 class="fw-bold mb-2">İcazə tələb olunur</h6>
                    <p class="text-muted small mb-0">
                        Bu mağazada idarəetmə icazəniz yoxdur. Elanları görmək və ya mağaza
                        məlumatlarını dəyişmək üçün mağaza sahibindən icazə istəyin.
                    </p>
                </div>
            @elseif ($tab === 'settings' && $canSettings)
                <section class="sf-ig__panel">
                    <form action="{{ route('storefront.store.panel.update') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <h2 class="sf-ig__panel-title">Mağaza məlumatları</h2>

                        @if ($errors->any())
                            <div class="sf-alert sf-alert--error mb-3">{{ $errors->first() }}</div>
                        @endif

                        <div class="sf-account__form">
                            <div class="sf-form-row">
                                <label>
                                    <span>Ad</span>
                                    <input type="text" name="name" value="{{ old('name', $storeName) }}" required maxlength="120">
                                </label>
                                <label>
                                    <span>Telefon</span>
                                    <input type="text" name="phone" value="{{ old('phone', $payload['phone'] ?? '') }}">
                                </label>
                            </div>
                            <label>
                                <span>Təsvir</span>
                                <textarea name="description" rows="3">{{ old('description', $storeDescription) }}</textarea>
                            </label>
                            <div class="sf-form-row">
                                <label>
                                    <span>Ünvan</span>
                                    <input type="text" name="address" value="{{ old('address', $payload['address'] ?? '') }}">
                                </label>
                                <label>
                                    <span>Loqo</span>
                                    <input type="file" name="logo" accept="image/*">
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="sf-ig-btn sf-ig-btn--primary w-100 mt-3 py-2">Yadda saxla</button>
                    </form>
                </section>
            @elseif ($canListings)
                @if (count($activeListings) === 0)
                    <div class="sf-ig-empty">
                        <div class="sf-ig-empty__icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                <circle cx="12" cy="13" r="4"></circle>
                            </svg>
                        </div>
                        <h3>{{ $tab === 'promoted' ? 'İrəli çəkilmiş elan yoxdur' : 'Hələ elan yoxdur' }}</h3>
                        <p>{{ $tab === 'promoted' ? 'Elanlarınızı irəli çəkərək satışlarınızı artırın.' : 'İlk elanınızı yerləşdirin və mağazanızı böyüdün.' }}</p>
                        <a href="/elan/ver" class="sf-ig-btn sf-ig-btn--primary px-4 py-2 mt-2">Yeni elan</a>
                    </div>
                @else
                    <div class="sf-ig__grid sf-ig-photo-grid">
                        @foreach ($activeListings as $listing)
                            @php
                                $item = $toPhoto($listing);
                                $hasMultiple = $item['image_count'] > 1;
                                $isPromoted = $item['is_promoted'] || $item['is_premium'];
                                $imgSrc = $item['image'] ?: '/assets/images/top-deals-item/topDealsItemThumb1_1.png';
                                $url = ! empty($item['slug']) ? '/elan/'.$item['slug'] : '#';
                            @endphp
                            <div class="sf-ig-tile">
                                <a href="{{ $url }}" class="sf-ig-tile__link">
                                    <img src="{{ $imgSrc }}" alt="{{ $item['title'] }}" loading="lazy"
                                         onerror="this.onerror=null;this.src='/assets/images/top-deals-item/topDealsItemThumb1_1.png';">
                                </a>

                                @if ($hasMultiple)
                                    <div class="sf-ig-tile__badge" title="Çoxlu şəkil">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                            <rect x="3" y="3" width="13" height="13" rx="2"></rect>
                                            <path d="M7 7h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7z"></path>
                                        </svg>
                                    </div>
                                @elseif ($isPromoted)
                                    <div class="sf-ig-tile__badge sf-ig-tile__badge--reels" title="İrəli çəkilmiş">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                            <polygon points="10 8 16 12 10 16 10 8"></polygon>
                                            <rect x="2" y="2" width="20" height="20" rx="3" fill="none" stroke="currentColor" stroke-width="2"></rect>
                                        </svg>
                                    </div>
                                @endif

                                <div class="sf-ig-tile__overlay">
                                    <a href="{{ $url }}" class="sf-ig-tile__info">
                                        <span class="sf-ig-tile__price">{{ number_format((float) $item['price'], 2) }} ₼</span>
                                        <span class="sf-ig-tile__title">{{ $item['title'] }}</span>
                                        <span class="sf-ig-tile__stats">
                                            <span><i class="fa-solid fa-eye"></i> {{ number_format($item['views']) }}</span>
                                            <span><i class="fa-solid fa-phone"></i> {{ number_format($item['contacts']) }}</span>
                                        </span>
                                    </a>
                                    <div class="sf-ig-tile__actions">
                                        <a href="{{ $url }}" class="sf-ig-tile__btn" title="Elana bax">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                        <form action="{{ route('storefront.listings.destroy', $listing['slug'] ?? $listing['id']) }}"
                                              method="POST" onsubmit="return confirm('Bu elanı silmək istəyirsiniz?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sf-ig-tile__btn sf-ig-tile__btn--danger" title="Elanı sil">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function setupDropdown(toggleId, menuId) {
                var btn = document.getElementById(toggleId);
                var menu = document.getElementById(menuId);
                if (!btn || !menu) return;

                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var wasOpen = menu.classList.contains('is-open');
                    document.querySelectorAll('.sf-ig-dropdown-menu.is-open').forEach(function (el) {
                        el.classList.remove('is-open');
                    });
                    if (!wasOpen) {
                        menu.classList.add('is-open');
                    }
                });
            }
            setupDropdown('sfIgTopMenuToggle', 'sfIgTopDropdown');
            setupDropdown('sfIgActionMoreBtn', 'sfIgActionDropdown');

            document.addEventListener('click', function () {
                document.querySelectorAll('.sf-ig-dropdown-menu.is-open').forEach(function (el) {
                    el.classList.remove('is-open');
                });
            });
        });
    </script>
</x-storefront.layouts.app>
