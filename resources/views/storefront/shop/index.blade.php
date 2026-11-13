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

    $ids = function (string $plural, string $singular) use ($query): array {
        $values = $query[$plural] ?? [];
        $values = is_array($values) ? $values : [$values];

        if (! empty($query[$singular])) {
            $values[] = $query[$singular];
        }

        return array_values(array_unique(array_filter(array_map('intval', $values), fn (int $id) => $id > 0)));
    };

    $selectedBrands = $ids('brands', 'brand');
    $selectedColors = $ids('colors', 'color');
    $selectedSizes = $ids('sizes', 'size');
    $selectedDynamic = $query['filters'] ?? [];

    $findName = function (array $list, int $id) {
        foreach ($list as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                return $item['name'] ?? null;
            }
        }

        return null;
    };

    // The root category whose own id or one of its children is selected.
    $selectedRoot = null;
    $selectedRootId = null;

    foreach ($categories as $category) {
        if ((int) $category['id'] === (int) $categoryId) {
            $selectedRoot = $category;
            $selectedRootId = (int) $category['id'];
            break;
        }

        foreach ($childrenOf($category) as $child) {
            if ((int) $child['id'] === (int) $categoryId) {
                $selectedRoot = $category;
                $selectedRootId = (int) $category['id'];
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

    foreach ($selectedBrands as $brandId) {
        $chips[] = [
            'label' => $findName($facets['brands'] ?? [], $brandId) ?? 'Brend',
            'url' => $buildUrl([], [], [['brands', $brandId], ['brand', $brandId]]),
        ];
    }

    foreach ($selectedColors as $colorId) {
        $chips[] = [
            'label' => $findName($facets['colors'] ?? [], $colorId) ?? 'Rəng',
            'url' => $buildUrl([], [], [['colors', $colorId], ['color', $colorId]]),
        ];
    }

    foreach ($selectedSizes as $sizeId) {
        $chips[] = [
            'label' => $findName($facets['sizes'] ?? [], $sizeId) ?? 'Ölçü',
            'url' => $buildUrl([], [], [['sizes', $sizeId], ['size', $sizeId]]),
        ];
    }

    if ($selected['price_min'] !== '' && $selected['price_min'] !== null) {
        $chips[] = ['label' => '≥ '.number_format((float) $selected['price_min'], 0).' ₼', 'url' => $buildUrl(['price_min'])];
    }

    if ($selected['price_max'] !== '' && $selected['price_max'] !== null) {
        $chips[] = ['label' => '≤ '.number_format((float) $selected['price_max'], 0).' ₼', 'url' => $buildUrl(['price_max'])];
    }

    foreach ($dynamicFilters as $filter) {
        foreach ((array) ($selectedDynamic[$filter['id']] ?? []) as $value) {
            $chips[] = ['label' => (string) $value, 'url' => $buildUrl([], [[$filter['id'], $value]])];
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
        {{-- Mobile shop header: category strips and quick filters, as in the former Svelte storefront. --}}
        <div class="sf-mobile-shop">
            <div class="sf-mobile-shop__sticky">
                <div class="sf-mobile-shop__heading">
                    <strong>{{ $pageTitle }}</strong>
                    <span>{{ number_format($total) }} elan</span>
                </div>

                <div class="sf-mobile-cat-strip" aria-label="Kateqoriyalar">
                    <a href="/shop" class="sf-mobile-cat-card {{ ! $categoryId ? 'is-active' : '' }}">
                        <span class="sf-mobile-cat-thumb"><i class="fa-regular fa-grid-2"></i></span>
                        <small>Hamısı</small>
                    </a>
                    @foreach ($categories as $category)
                        <a href="/shop?category={{ $category['id'] }}"
                           class="sf-mobile-cat-card {{ (int) $selectedRootId === (int) $category['id'] ? 'is-active' : '' }}">
                            <span class="sf-mobile-cat-thumb">
                                @if (! empty($category['image']))
                                    <img src="{{ $category['image'] }}" alt="{{ $category['name'] }}" loading="lazy">
                                @else
                                    <i class="fa-regular fa-grid-2"></i>
                                @endif
                            </span>
                            <small>{{ $category['name'] }}</small>
                        </a>
                    @endforeach
                </div>

                @if ($subcategories !== [])
                    <div class="sf-mobile-subcat-strip" aria-label="Alt kateqoriyalar">
                        <a href="/shop?category={{ $selectedRootId }}"
                           class="sf-mobile-subcat-chip {{ (int) $categoryId === (int) $selectedRootId ? 'is-active' : '' }}">
                            Hamısı
                        </a>
                        @foreach ($subcategories as $child)
                            <a href="/shop?category={{ $child['id'] }}"
                               class="sf-mobile-subcat-chip {{ (int) $categoryId === (int) $child['id'] ? 'is-active' : '' }}">
                                {{ $child['name'] }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="sf-mobile-quick" aria-label="Sürətli filtrlər">
                    <button type="button" class="sf-mobile-chip" data-sf-mobile-filters>
                        <i class="fa-solid fa-sliders"></i>
                        <span>Filtrlər</span>
                        @if ($activeFilterCount)
                            <span class="sf-mobile-chip__badge">{{ $activeFilterCount }}</span>
                        @endif
                    </button>

                    <label class="sf-mobile-select sf-mobile-select--sort" aria-label="Sıralama">
                        <i class="fa-solid fa-arrow-down-wide-short" aria-hidden="true"></i>
                        <select data-sf-quick="sort">
                            @foreach ($sorts as $sort)
                                <option value="{{ $sort }}" @selected($currentSort === $sort)>{{ $sortLabels[$sort] ?? $sort }}</option>
                            @endforeach
                        </select>
                    </label>

                    @php
                        $quickFacets = [
                            ['key' => 'brands', 'label' => 'Brend', 'items' => $facets['brands'] ?? [], 'selected' => $selectedBrands],
                            ['key' => 'colors', 'label' => 'Rəng', 'items' => $facets['colors'] ?? [], 'selected' => $selectedColors],
                            ['key' => 'sizes', 'label' => 'Ölçü', 'items' => $facets['sizes'] ?? [], 'selected' => $selectedSizes],
                        ];
                    @endphp

                    @foreach ($quickFacets as $facet)
                        @if (! empty($facet['items']))
                            <label class="sf-mobile-select">
                                <span>{{ $facet['label'] }}</span>
                                <select data-sf-quick="{{ $facet['key'] }}">
                                    <option value="">Hamısı</option>
                                    @foreach ($facet['items'] as $item)
                                        <option value="{{ $item['id'] }}" @selected(in_array((int) $item['id'], $facet['selected'], true))>
                                            {{ $item['name'] ?? $facet['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                    @endforeach

                    @foreach (array_slice($dynamicFilters, 0, 5) as $filter)
                        @php
                            $filterTitle = is_array($filter['title'] ?? null)
                                ? ($filter['title']['az'] ?? reset($filter['title']))
                                : ($filter['title'] ?? 'Parametr');
                            $filterValues = array_values(array_unique(array_filter(! empty($filter['options']) ? $filter['options'] : ($filter['values'] ?? []))));
                            $filterPicked = (array) ($selectedDynamic[$filter['id']] ?? []);
                        @endphp
                        @if ($filterValues !== [])
                            <label class="sf-mobile-select">
                                <span>{{ $filterTitle }}</span>
                                <select data-sf-quick="filters" data-sf-filter="{{ $filter['id'] }}">
                                    <option value="">Hamısı</option>
                                    @foreach ($filterValues as $value)
                                        <option value="{{ $value }}" @selected(in_array((string) $value, array_map('strval', $filterPicked), true))>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                    @endforeach
                </div>
            </div>

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
