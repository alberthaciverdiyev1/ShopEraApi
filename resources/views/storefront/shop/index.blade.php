@php
    $childrenOf = function ($category) {
        $children = $category['children'] ?? [];

        if ($children instanceof \Illuminate\Http\Resources\Json\ResourceCollection) {
            $children = $children->resolve();
        }

        return array_values((array) $children);
    };

    $activeCategory = collect($categories)->flatMap(function ($category) use ($childrenOf) {
        return array_merge([$category], $childrenOf($category));
    })->firstWhere('id', (int) $categoryId);
    $pageTitle = $activeCategory['name'] ?? 'Bütün elanlar';
    $total = (int) ($meta['total'] ?? 0);
    $currentSort = $query['sort'] ?? 'created_at:desc';
    $sortLabels = [
        'created_at:desc' => 'Ən yeni',
        'price:asc' => 'Qiymət: əvvəl ucuz',
        'price:desc' => 'Qiymət: əvvəl baha',
        'sales_count:desc' => 'Ən çox satılan',
    ];

    $selected = [
        'search' => $query['search'] ?? '',
        'price_min' => $query['price_min'] ?? '',
        'price_max' => $query['price_max'] ?? '',
    ];

    $values = function (string $plural, string $singular) use ($query): array {
        $values = $query[$plural] ?? [];
        $values = is_array($values) ? $values : [$values];

        if (! empty($query[$singular])) {
            $values[] = $query[$singular];
        }

        return array_values(array_unique(array_filter(
            array_map(fn ($value) => (string) $value, $values),
            fn (string $value) => $value !== ''
        )));
    };

    $selectedBrands = $values('brands', 'brand');
    $selectedColors = $values('colors', 'color');
    $selectedSizes = $values('sizes', 'size');
    $selectedDynamic = $query['filters'] ?? [];

    // Selected values are slugs; ids are still accepted for old links.
    $selectedDynamicFor = function (array $filter) use ($selectedDynamic): array {
        $key = $filter['slug'] ?? $filter['id'];

        return (array) ($selectedDynamic[$key] ?? $selectedDynamic[$filter['id']] ?? []);
    };

    $findName = function (array $list, string $value) {
        foreach ($list as $item) {
            if ((string) ($item['slug'] ?? '') === $value || (string) ($item['id'] ?? '') === $value) {
                return $item['name'] ?? null;
            }
        }

        return null;
    };

    // The root category whose own id or one of its children is selected.
    $selectedRoot = null;
    $selectedRootId = null;
    $selectedRootSlug = null;

    foreach ($categories as $category) {
        if ((int) $category['id'] === (int) $categoryId) {
            $selectedRoot = $category;
            $selectedRootId = (int) $category['id'];
            $selectedRootSlug = $category['slug'] ?? $category['id'];
            break;
        }

        foreach ($childrenOf($category) as $child) {
            if ((int) $child['id'] === (int) $categoryId) {
                $selectedRoot = $category;
                $selectedRootId = (int) $category['id'];
                $selectedRootSlug = $category['slug'] ?? $category['id'];
                break 2;
            }
        }
    }

    $subcategories = $selectedRoot ? $childrenOf($selectedRoot) : [];

    // Active filter chips, shared with the sidebar form.
    $buildUrl = function (array $dropKeys = [], array $dropFilterValues = [], array $removeValues = []) use ($query) {
        $params = collect($query)->except('page')->all();

        foreach ($dropKeys as $key) {
            unset($params[$key]);
        }

        foreach ($removeValues as [$key, $value]) {
            if (! isset($params[$key])) {
                continue;
            }

            $remaining = array_values(array_filter(
                is_array($params[$key]) ? $params[$key] : [$params[$key]],
                fn ($item) => (string) $item !== (string) $value
            ));

            if ($remaining === []) {
                unset($params[$key]);
            } else {
                $params[$key] = $remaining;
            }
        }

        foreach ($dropFilterValues as [$filterId, $value]) {
            if (! isset($params['filters'][$filterId])) {
                continue;
            }

            $remaining = array_values(array_filter(
                (array) $params['filters'][$filterId],
                fn ($item) => (string) $item !== (string) $value
            ));

            if ($remaining === []) {
                unset($params['filters'][$filterId]);
            } else {
                $params['filters'][$filterId] = $remaining;
            }

            if (isset($params['filters']) && $params['filters'] === []) {
                unset($params['filters']);
            }
        }

        $qs = http_build_query($params);

        return $qs === '' ? '/shop' : '/shop?'.$qs;
    };

    $chips = [];
    if ($selected['search'] !== '') {
        $chips[] = ['label' => '“'.$selected['search'].'”', 'url' => $buildUrl(['search'])];
    }

    if ($activeCategory) {
        $chips[] = ['label' => $activeCategory['name'], 'url' => $buildUrl(['category'])];
    }

    foreach ($selectedBrands as $brandSlug) {
        $chips[] = [
            'label' => $findName($facets['brands'] ?? [], $brandSlug) ?? 'Brend',
            'url' => $buildUrl([], [], [['brands', $brandSlug], ['brand', $brandSlug]]),
        ];
    }

    foreach ($selectedColors as $colorSlug) {
        $chips[] = [
            'label' => $findName($facets['colors'] ?? [], $colorSlug) ?? 'Rəng',
            'url' => $buildUrl([], [], [['colors', $colorSlug], ['color', $colorSlug]]),
        ];
    }

    foreach ($selectedSizes as $sizeSlug) {
        $chips[] = [
            'label' => $findName($facets['sizes'] ?? [], $sizeSlug) ?? 'Ölçü',
            'url' => $buildUrl([], [], [['sizes', $sizeSlug], ['size', $sizeSlug]]),
        ];
    }

    if ($selected['price_min'] !== '' && $selected['price_min'] !== null) {
        $chips[] = ['label' => '≥ '.number_format((float) $selected['price_min'], 0).' ₼', 'url' => $buildUrl(['price_min'])];
    }

    if ($selected['price_max'] !== '' && $selected['price_max'] !== null) {
        $chips[] = ['label' => '≤ '.number_format((float) $selected['price_max'], 0).' ₼', 'url' => $buildUrl(['price_max'])];
    }

    foreach ($dynamicFilters as $filter) {
        foreach ($selectedDynamicFor($filter) as $value) {
            $chips[] = ['label' => (string) $value, 'url' => $buildUrl([], [[$filter['slug'] ?? $filter['id'], $value]])];
        }
    }

    $activeFilterCount = count($chips);
@endphp

<x-storefront.layouts.app
    :title="$pageTitle.' — ShopEra'"
    :description="'ShopEra elanları: '.$pageTitle"
    :categories="$categories">

    @if (false)
        <x-storefront.breadcrumb title="Elanlar" />
    @endif

    <div class="container sf-shop">
        {{-- Mobile shop header: Trendyol-style top bar, split sort/filter bar, and quick filter pills --}}
        <div class="sf-mobile-shop trendyol-shop-container">
            {{-- 1. Top Bar: Back button (<) + Centered Title & Subtitle --}}
            <div class="trendyol-shop-topbar">
                <button type="button" class="trendyol-shop-back" onclick="if(history.length > 1){ history.back(); } else { window.location.href='/'; }" aria-label="Geri">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <div class="trendyol-shop-heading">
                    <h1 class="trendyol-shop-title">{{ $pageTitle }}</h1>
                    <span class="trendyol-shop-subtitle">{{ number_format($total) }}+ Məhsul</span>
                </div>
            </div>

            {{-- 2. 50/50 Split Action Bar: Sort (⇅) and Filter (🎚) --}}
            <div class="trendyol-action-bar">
                <button type="button" class="trendyol-action-btn trendyol-action-btn--sort" data-sf-sort-modal-open>
                    <i class="fa-solid fa-arrow-up-arrow-down trendyol-action-icon"></i>
                    <span class="trendyol-action-text">{{ $sortLabels[$currentSort] ?? 'Sıralama' }}</span>
                </button>

                <button type="button" class="trendyol-action-btn trendyol-action-btn--filter" data-sf-mobile-filters>
                    <i class="fa-regular fa-sliders trendyol-action-icon"></i>
                    <span class="trendyol-action-text">Filtirlə</span>
                    @if ($activeFilterCount)
                        <span class="trendyol-filter-badge">({{ $activeFilterCount }})</span>
                    @endif
                </button>
            </div>

            {{-- 3. Horizontal Scrolling Quick Filter Pills --}}
            <div class="trendyol-pills-scroll" aria-label="Sürətli filtrlər">
                {{-- Discount Quick Pill --}}
                @php
                    $hasDiscount = ! empty($query['has_discount']);
                    $discountToggleUrl = $hasDiscount
                        ? $buildUrl(['has_discount'])
                        : '/shop?'.http_build_query(array_merge($query, ['has_discount' => 1, 'page' => 1]));
                @endphp
                <a href="{{ $discountToggleUrl }}" class="trendyol-pill-chip {{ $hasDiscount ? 'is-active' : '' }}">
                    <span>Endirimli məhsullar</span>
                </a>

                {{-- Brands Quick Pill --}}
                @if (! empty($facets['brands']))
                    <label class="trendyol-pill-select {{ ! empty($selectedBrands) ? 'is-active' : '' }}">
                        <span>Brend</span>
                        <i class="fa-regular fa-chevron-down pill-chevron"></i>
                        <select data-sf-quick="brands">
                            <option value="">Brend: Hamısı</option>
                            @foreach ($facets['brands'] as $item)
                                @php $facetValue = (string) ($item['slug'] ?? $item['id']); @endphp
                                <option value="{{ $facetValue }}" @selected(in_array($facetValue, $selectedBrands, true))>
                                    {{ $item['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif

                {{-- Dynamic Attributes (e.g. Yaxa tipi, Material, etc.) --}}
                @foreach (array_slice($dynamicFilters, 0, 6) as $filter)
                    @php
                        $filterTitle = is_array($filter['title'] ?? null)
                            ? ($filter['title']['az'] ?? reset($filter['title']))
                            : ($filter['title'] ?? 'Parametr');
                        $filterValues = array_values(array_unique(array_filter(! empty($filter['options']) ? $filter['options'] : ($filter['values'] ?? []))));
                        $filterPicked = (array) ($selectedDynamic[$filter['slug'] ?? $filter['id']] ?? $selectedDynamic[$filter['id']] ?? []);
                    @endphp
                    @if ($filterValues !== [])
                        <label class="trendyol-pill-select {{ ! empty($filterPicked) ? 'is-active' : '' }}">
                            <span>{{ $filterTitle }}</span>
                            <i class="fa-regular fa-chevron-down pill-chevron"></i>
                            <select data-sf-quick="filters" data-sf-filter="{{ $filter['slug'] ?? $filter['id'] }}">
                                <option value="">{{ $filterTitle }}: Hamısı</option>
                                @foreach ($filterValues as $value)
                                    <option value="{{ $value }}" @selected(in_array((string) $value, array_map('strval', $filterPicked), true))>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                @endforeach

                {{-- Colors Quick Pill --}}
                @if (! empty($facets['colors']))
                    <label class="trendyol-pill-select {{ ! empty($selectedColors) ? 'is-active' : '' }}">
                        <span>Rəng</span>
                        <i class="fa-regular fa-chevron-down pill-chevron"></i>
                        <select data-sf-quick="colors">
                            <option value="">Rəng: Hamısı</option>
                            @foreach ($facets['colors'] as $item)
                                @php $facetValue = (string) ($item['slug'] ?? $item['id']); @endphp
                                <option value="{{ $facetValue }}" @selected(in_array($facetValue, $selectedColors, true))>
                                    {{ $item['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif

                {{-- Sizes Quick Pill --}}
                @if (! empty($facets['sizes']))
                    <label class="trendyol-pill-select {{ ! empty($selectedSizes) ? 'is-active' : '' }}">
                        <span>Ölçü</span>
                        <i class="fa-regular fa-chevron-down pill-chevron"></i>
                        <select data-sf-quick="sizes">
                            <option value="">Ölçü: Hamısı</option>
                            @foreach ($facets['sizes'] as $item)
                                @php $facetValue = (string) ($item['slug'] ?? $item['id']); @endphp
                                <option value="{{ $facetValue }}" @selected(in_array($facetValue, $selectedSizes, true))>
                                    {{ $item['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>

            {{-- 4. Active Chips --}}
            @if ($chips)
                <div class="sf-mobile-active-chips" aria-label="Aktiv filtrlər">
                    @foreach ($chips as $chip)
                        <a href="{{ $chip['url'] }}" class="sf-filter-chip">
                            {{ $chip['label'] }}
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endforeach
                    <a href="/shop" class="sf-mobile-active-chips__clear">Hamısını təmizlə</a>
                </div>
            @endif

            {{-- 5. Sort Bottom Sheet Modal --}}
            <div class="sf-sort-modal" id="sf-sort-modal" data-sf-sort-modal hidden>
                <button type="button" class="sf-sort-backdrop" data-sf-sort-close aria-label="Bağla"></button>
                <div class="sf-sort-sheet">
                    <div class="sf-sort-sheet-head">
                        <strong>Sıralama</strong>
                        <button type="button" class="sf-sort-sheet-close" data-sf-sort-close aria-label="Bağla">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="sf-sort-sheet-body">
                        @foreach ($sorts as $sort)
                            <button type="button"
                                    class="sf-sort-sheet-item @if ($currentSort === $sort) is-active @endif"
                                    data-sf-sort-choose="{{ $sort }}">
                                <span>{{ $sortLabels[$sort] ?? $sort }}</span>
                                <span class="sf-sort-radio-circle"></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <header class="sf-shop__head">
            <div>
                <h1 class="sf-shop__title">{{ $pageTitle }}</h1>
                <p class="sf-shop__count">{{ number_format($total) }} elan tapıldı</p>
            </div>

            <div class="sf-shop__toolbar">
                <form class="sf-shop-sort" action="/shop" method="GET">
                    @foreach (array_diff_key($query, ['sort' => true, 'page' => true]) as $name => $value)
                        @if (is_scalar($value) && $name !== 'filters')
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @elseif (is_array($value) && in_array($name, ['brands', 'colors', 'sizes'], true))
                            @foreach ($value as $item)
                                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                            @endforeach
                        @endif
                    @endforeach
                    @foreach ((array) ($query['filters'] ?? []) as $filterId => $values)
                        @foreach ((array) $values as $value)
                            <input type="hidden" name="filters[{{ $filterId }}][]" value="{{ $value }}">
                        @endforeach
                    @endforeach
                    <label for="sf-toolbar-sort">Sıralama:</label>
                    <select id="sf-toolbar-sort" name="sort" data-sf-auto-submit>
                        @foreach ($sorts as $sort)
                            <option value="{{ $sort }}" @selected($currentSort === $sort)>{{ $sortLabels[$sort] ?? $sort }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </header>

        <div class="sf-shop__layout">
            <aside class="sf-shop__sidebar" data-sf-sidebar>
                <div class="sf-mobile-drawer__head">
                    <div class="sf-mobile-drawer__title">
                        <button type="button" class="sf-shop__sidebar-close" data-sf-sidebar-close aria-label="Geri">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <strong>Filtrlər</strong>
                        @if ($activeFilterCount)
                            <span class="sf-filters-head__badge">{{ $activeFilterCount }}</span>
                        @endif
                    </div>
                    <a href="/shop" class="sf-mobile-drawer__reset">Sıfırla</a>
                </div>

                <div class="sf-mobile-drawer__body">
                    <x-storefront.shop.filters
                        :categories="$categories"
                        :facets="$facets"
                        :dynamic-filters="$dynamicFilters"
                        :category-id="$categoryId"
                        :sorts="$sorts"
                        :query="$query"
                        :active-chips="$chips" />
                </div>

                <div class="sf-mobile-drawer__foot">
                    <button type="submit" form="sf-filter-form" class="sf-btn sf-btn--primary sf-mobile-drawer__apply">
                        Nəticələri göstər ({{ number_format($total) }})
                    </button>
                </div>
            </aside>

            <div class="sf-shop__backdrop" data-sf-sidebar-close hidden></div>

            <section class="sf-shop__content">
                @if (count($items) > 0)
                    <div class="sf-product-grid">
                        @foreach ($items as $product)
                            <x-storefront.product-card :product="$product" />
                        @endforeach
                    </div>

                    <x-storefront.pagination :meta="$meta" />

                    <div class="sf-shop__more" data-sf-infinite hidden>
                        <span class="sf-shop__more-spinner" aria-hidden="true"></span>
                    </div>
                @else
                    <div class="sf-empty">
                        <i class="fa-regular fa-magnifying-glass"></i>
                        <h2>Nəticə tapılmadı</h2>
                        <p>Filtrləri dəyişdirərək yenidən yoxlayın.</p>
                        <a href="/shop" class="sf-btn sf-btn--primary">Bütün elanlar</a>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-storefront.layouts.app>
