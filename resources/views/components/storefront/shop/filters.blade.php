@props([
    'categories' => [],
    'facets' => [],
    'dynamicFilters' => [],
    'categoryId' => null,
    'sorts' => [],
    'query' => [],
    'activeChips' => null,
])

@php
    $selected = [
        'search' => $query['search'] ?? '',
        'price_min' => $query['price_min'] ?? '',
        'price_max' => $query['price_max'] ?? '',
        'sort' => $query['sort'] ?? 'created_at:desc',
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
    $selectedVendors = $values('vendors', 'vendor');
    $selectedDynamic = $query['filters'] ?? [];

    // Selected values are slugs; ids are still accepted for old links.
    $selectedDynamicFor = function (array $filter) use ($selectedDynamic): array {
        $key = $filter['slug'] ?? $filter['id'];

        return (array) ($selectedDynamic[$key] ?? $selectedDynamic[$filter['id']] ?? []);
    };

    $priceMin = (float) ($facets['price']['min'] ?? 0);
    $priceMax = (float) ($facets['price']['max'] ?? 0);

    $sorts = $sorts ?: ['created_at:desc'];

    $findName = function (array $list, string $value) {
        foreach ($list as $item) {
            if ((string) ($item['slug'] ?? '') === $value || (string) ($item['id'] ?? '') === $value) {
                return $item['name'] ?? null;
            }
        }

        return null;
    };

    $activeCategoryName = null;
    if ($categoryId) {
        foreach ($categories as $category) {
            if ((int) $category['id'] === (int) $categoryId) {
                $activeCategoryName = $category['name'];
                break;
            }
            foreach ($category['children'] ?? [] as $child) {
                if ((int) $child['id'] === (int) $categoryId) {
                    $activeCategoryName = $child['name'];
                    break 2;
                }
            }
        }
    }

    $childrenOf = function ($category): array {
        $children = $category['children'] ?? [];

        if ($children instanceof \Illuminate\Http\Resources\Json\ResourceCollection) {
            $children = $children->resolve();
        }

        return array_values((array) $children);
    };

    // The category picker shows roots only; the subcategory picker appears once
    // a root is chosen and holds that root's children.
    $selectedRoot = null;
    $selectedChildId = null;

    foreach ($categories as $category) {
        if ((int) ($category['id'] ?? 0) === (int) $categoryId) {
            $selectedRoot = $category;
            break;
        }

        foreach ($childrenOf($category) as $child) {
            if ((int) ($child['id'] ?? 0) === (int) $categoryId) {
                $selectedRoot = $category;
                $selectedChildId = (int) $child['id'];
                break 2;
            }
        }
    }

    $selectedRootId = (int) ($selectedRoot['id'] ?? 0) ?: null;
    $subcategories = $selectedRoot ? $childrenOf($selectedRoot) : [];

    $buildUrl = function (array $dropKeys = [], array $dropFilterValues = [], array $removeValues = []) use ($query) {
        $params = collect($query)->except('page')->all();

        foreach ($dropKeys as $key) {
            unset($params[$key]);
        }

        // A pair is [query key, value]; the same id can come from the legacy
        // singular key or the multi-select array, so callers pass both.
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

    // The page may hand its chips in (shared with the mobile header); otherwise
    // build them here for views that render the sidebar on its own.
    $chips = $activeChips ?? [];

    if ($activeChips === null) {
    if ($selected['search'] !== '') {
        $chips[] = ['label' => '“'.$selected['search'].'”', 'url' => $buildUrl(['search'])];
    }

    if ($activeCategoryName) {
        $chips[] = ['label' => $activeCategoryName, 'url' => $buildUrl(['category'])];
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

    foreach ($selectedVendors as $vendorSlug) {
        $chips[] = [
            'label' => $findName($facets['vendors'] ?? [], $vendorSlug) ?? 'Mağaza',
            'url' => $buildUrl([], [], [['vendors', $vendorSlug], ['vendor', $vendorSlug]]),
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

    }
    $activeCount = count($chips);
@endphp

<form id="sf-filter-form" class="sf-shop-filters" action="/shop" method="GET" data-sf-filter-form>
    <input type="hidden" name="page" value="1">
    {{-- Sorting lives in the page toolbar; keep the chosen value when filters are applied. --}}
    <input type="hidden" name="sort" value="{{ $selected['sort'] }}">
    @if ($selected['search'] !== '')
        <input type="hidden" name="search" value="{{ $selected['search'] }}">
    @endif
    @if (! empty($query['vendor_id']))
        <input type="hidden" name="vendor_id" value="{{ $query['vendor_id'] }}">
    @endif

    <div class="sf-filters-head">
        <div class="sf-filters-head__title">
            Filtrlər
            @if ($activeCount)
                <span class="sf-filters-head__badge">{{ $activeCount }}</span>
            @endif
        </div>
        @if ($activeCount)
            <a href="/shop" class="sf-filters-head__reset">Təmizlə</a>
        @endif
    </div>

    @if ($chips)
        <div class="sf-filter-chips" aria-label="Aktiv filtrlər">
            @foreach ($chips as $chip)
                <a href="{{ $chip['url'] }}" class="sf-filter-chip">
                    {{ $chip['label'] }}
                    <i class="fa-solid fa-xmark"></i>
                </a>
            @endforeach
        </div>
    @endif

    <details class="sf-filter-card" open>
        <summary class="sf-filter-head">
            <span>Kateqoriya</span>
            <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
        </summary>
        <div class="sf-filter-selects">
            {{-- Only one of the two pickers submits "category" so the URL never
                 carries the parameter twice. --}}
            <select @if (! $selectedChildId) name="category" @endif data-sf-category-root aria-label="Kateqoriya">
                <option value="">Bütün kateqoriyalar</option>
                @foreach ($categories as $category)
                    <option value="{{ $category['slug'] ?? $category['id'] }}" @selected($selectedRootId === (int) $category['id'])>
                        {{ $category['name'] }}
                    </option>
                @endforeach
            </select>

            @if ($selectedRoot && $subcategories)
                {{-- The subcategory submits as "category" only while one is chosen;
                     otherwise the root picker above supplies the value. --}}
                <select @if ($selectedChildId) name="category" @endif data-sf-category-child aria-label="Alt kateqoriya">
                    <option value="">Bütün alt kateqoriyalar</option>
                    @foreach ($subcategories as $child)
                        <option value="{{ $child['slug'] ?? $child['id'] }}" @selected($selectedChildId === (int) $child['id'])>
                            {{ $child['name'] }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    </details>

    @if ($priceMax > 0)
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Qiymət</span>
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-price-range">
                <label class="sf-price-field">
                    <span class="sf-price-field__box">
                        <input type="number" name="price_min" min="0" max="{{ ceil($priceMax) }}" step="1"
                               value="{{ $selected['price_min'] }}" placeholder="{{ floor($priceMin) }}"
                               aria-label="Ən az qiymət" data-sf-auto-submit>
                        <span>₼</span>
                    </span>
                </label>
                <span class="sf-price-range__dash" aria-hidden="true">—</span>
                <label class="sf-price-field">
                    <span class="sf-price-field__box">
                        <input type="number" name="price_max" min="0" max="{{ ceil($priceMax) }}" step="1"
                               value="{{ $selected['price_max'] }}" placeholder="{{ ceil($priceMax) }}"
                               aria-label="Ən çox qiymət" data-sf-auto-submit>
                        <span>₼</span>
                    </span>
                </label>
            </div>
            <p class="sf-price-hint">Mövcud aralıq: {{ number_format($priceMin, 0) }} – {{ number_format($priceMax, 0) }} ₼</p>
        </details>
    @endif

    @foreach ($dynamicFilters as $filter)
        @php
            $title = is_array($filter['title'] ?? null)
                ? ($filter['title']['az'] ?? reset($filter['title']))
                : ($filter['title'] ?? 'Parametr');
            $values = ! empty($filter['options']) ? $filter['options'] : ($filter['values'] ?? []);
            $values = array_values(array_unique(array_filter($values)));
            $picked = array_map('strval', $selectedDynamicFor($filter));
            // Options are a fixed list; product-driven values can grow, so those
            // are loaded page by page from the server.
            $serverValues = empty($filter['options']);
            $pickedExtras = collect($values)->skip(10)
                ->filter(fn ($value) => in_array((string) $value, $picked, true))->values();
            $visibleValues = $serverValues
                ? collect($values)->take(10)->concat($pickedExtras)->values()
                : collect($values);
            $extraValues = $serverValues
                ? count($values) - $visibleValues->count()
                : collect($values)->skip(10)
                    ->reject(fn ($value) => in_array((string) $value, $picked, true))->count();
        @endphp
        @if ($values !== [])
            <details class="sf-filter-card" open>
                <summary class="sf-filter-head">
                    <span>{{ $title }}</span>
                    <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
                </summary>
                <div class="sf-filter-options sf-filter-options--check">
                    @foreach ($visibleValues as $index => $value)
                        <label class="sf-filter-option"
                               @if (! $serverValues && $index >= 10 && ! in_array((string) $value, $picked, true)) data-sf-extra hidden @endif>
                            <input type="checkbox" name="filters[{{ $filter['slug'] ?? $filter['id'] }}][]" value="{{ $value }}"
                                   @checked(in_array((string) $value, $picked, true)) data-sf-auto-submit>
                            <span class="sf-filter-option__text">{{ $value }}</span>
                        </label>
                    @endforeach
                </div>
                @if ($extraValues > 0)
                    <button type="button" class="sf-filter-more" data-sf-show-more
                            @if ($serverValues)
                                data-sf-facet="filter"
                                data-sf-slug="{{ $filter['slug'] ?? $filter['id'] }}"
                                data-sf-page="2"
                                data-sf-per-page="10"
                                data-sf-category="{{ $categoryId ?? '' }}"
                            @endif
                            data-more-label="Daha çox göstər (+{{ $extraValues }})"
                            data-less-label="Daha az göstər">Daha çox göstər (+{{ $extraValues }})</button>
                @endif
            </details>
        @endif
    @endforeach

    @if (! empty($facets['vendors']))
        @php
            $vendorTotal = count($facets['vendors']);
            $visibleVendors = collect($facets['vendors'])->take(10)
                ->concat(collect($facets['vendors'])->skip(10)
                    ->filter(fn ($vendor) => in_array((string) ($vendor['slug'] ?? $vendor['id']), $selectedVendors, true)))
                ->values();
            $extraVendors = $vendorTotal - $visibleVendors->count();
        @endphp
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Mağazalar</span>
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-filter-options">
                @foreach ($visibleVendors as $vendor)
                    @php $vendorValue = (string) ($vendor['slug'] ?? $vendor['id']); @endphp
                    <label class="sf-filter-option">
                        <input type="checkbox" name="vendors[]" value="{{ $vendorValue }}"
                               @checked(in_array($vendorValue, $selectedVendors, true)) data-sf-auto-submit>
                        <span class="sf-filter-option__text">{{ $vendor['name'] }}</span>
                        <span class="sf-filter-option__count">{{ $vendor['products_count'] ?? 0 }}</span>
                    </label>
                @endforeach
            </div>
            @if ($extraVendors > 0)
                <button type="button" class="sf-filter-more" data-sf-show-more
                        data-sf-facet="vendors"
                        data-sf-page="2"
                        data-sf-per-page="10"
                        data-sf-category="{{ $categoryId ?? '' }}"
                        data-more-label="Daha çox göstər (+{{ $extraVendors }})">Daha çox göstər (+{{ $extraVendors }})</button>
            @endif
        </details>
    @endif

    @if (! empty($facets['brands']))
        @php
            $brandTotal = count($facets['brands']);
            $visibleBrands = collect($facets['brands'])->take(10)
                ->concat(collect($facets['brands'])->skip(10)
                    ->filter(fn ($brand) => in_array((string) ($brand['slug'] ?? $brand['id']), $selectedBrands, true)))
                ->values();
            $extraBrands = $brandTotal - $visibleBrands->count();
        @endphp
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Brendlər</span>
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-filter-options">
                @foreach ($visibleBrands as $brand)
                    @php $brandValue = (string) ($brand['slug'] ?? $brand['id']); @endphp
                    <label class="sf-filter-option">
                        <input type="checkbox" name="brands[]" value="{{ $brandValue }}"
                               @checked(in_array($brandValue, $selectedBrands, true)) data-sf-auto-submit>
                        <span class="sf-filter-option__text">
                            @if (! empty($brand['image']))
                                <img src="{{ $brand['image'] }}" alt="" loading="lazy" onerror="this.remove()">
                            @endif
                            {{ $brand['name'] }}
                        </span>
                        <span class="sf-filter-option__count">{{ $brand['products_count'] ?? 0 }}</span>
                    </label>
                @endforeach
            </div>
            @if ($extraBrands > 0)
                <button type="button" class="sf-filter-more" data-sf-show-more
                        data-sf-facet="brands"
                        data-sf-page="2"
                        data-sf-per-page="10"
                        data-sf-category="{{ $categoryId ?? '' }}"
                        data-more-label="Daha çox göstər (+{{ $extraBrands }})">Daha çox göstər (+{{ $extraBrands }})</button>
            @endif
        </details>
    @endif

    @if (! empty($facets['colors']))
        @php
            $colorTotal = count($facets['colors']);
            $visibleColors = collect($facets['colors'])->take(10)
                ->concat(collect($facets['colors'])->skip(10)
                    ->filter(fn ($color) => in_array((string) ($color['slug'] ?? $color['id']), $selectedColors, true)))
                ->values();
            $extraColors = $colorTotal - $visibleColors->count();
        @endphp
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Rənglər</span>
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-filter-options">
                @foreach ($visibleColors as $color)
                    @php $colorValue = (string) ($color['slug'] ?? $color['id']); @endphp
                    <label class="sf-filter-option">
                        <input type="checkbox" name="colors[]" value="{{ $colorValue }}"
                               @checked(in_array($colorValue, $selectedColors, true)) data-sf-auto-submit>
                        <span class="sf-filter-option__text">
                            <span class="sf-swatch" style="background-color: {{ $color['hex'] ?? '#cccccc' }}"></span>
                            {{ $color['name'] ?? '—' }}
                        </span>
                        <span class="sf-filter-option__count">{{ $color['products_count'] ?? 0 }}</span>
                    </label>
                @endforeach
            </div>
            @if ($extraColors > 0)
                <button type="button" class="sf-filter-more" data-sf-show-more
                        data-sf-facet="colors"
                        data-sf-page="2"
                        data-sf-per-page="10"
                        data-sf-category="{{ $categoryId ?? '' }}"
                        data-more-label="Daha çox göstər (+{{ $extraColors }})">Daha çox göstər (+{{ $extraColors }})</button>
            @endif
        </details>
    @endif

    @if (! empty($facets['sizes']))
        @php
            $sizeTotal = count($facets['sizes']);
            $visibleSizes = collect($facets['sizes'])->take(12)
                ->concat(collect($facets['sizes'])->skip(12)
                    ->filter(fn ($size) => in_array((string) ($size['slug'] ?? $size['id']), $selectedSizes, true)))
                ->values();
            $extraSizes = $sizeTotal - $visibleSizes->count();
        @endphp
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Ölçülər</span>
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-size-grid">
                @foreach ($visibleSizes as $size)
                    @php $sizeValue = (string) ($size['slug'] ?? $size['id']); @endphp
                    <label class="sf-size-option">
                        <input type="checkbox" name="sizes[]" value="{{ $sizeValue }}"
                               @checked(in_array($sizeValue, $selectedSizes, true)) data-sf-auto-submit>
                        <span>{{ $size['name'] ?? '—' }}</span>
                        <span class="sf-size-option__count">{{ $size['products_count'] ?? 0 }}</span>
                    </label>
                @endforeach
            </div>
            @if ($extraSizes > 0)
                <button type="button" class="sf-filter-more" data-sf-show-more
                        data-sf-facet="sizes"
                        data-sf-page="2"
                        data-sf-per-page="12"
                        data-sf-category="{{ $categoryId ?? '' }}"
                        data-more-label="Daha çox göstər (+{{ $extraSizes }})">Daha çox göstər (+{{ $extraSizes }})</button>
            @endif
        </details>
    @endif

    <div class="sf-filter-actions">
        <button type="submit" class="sf-btn sf-btn--primary">Nəticələri göstər</button>
        <a href="/shop" class="sf-btn sf-btn--ghost">Sıfırla</a>
    </div>
</form>
