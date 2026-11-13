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
        <select name="category" data-sf-auto-submit>
            <option value="">Bütün kateqoriyalar</option>
            @foreach ($categories as $category)
                <option value="{{ $category['slug'] ?? $category['id'] }}" @selected((int) $categoryId === (int) $category['id'])>
                    {{ $category['name'] }}
                </option>
                @foreach ($category['children'] ?? [] as $child)
                    <option value="{{ $child['slug'] ?? $child['id'] }}" @selected((int) $categoryId === (int) $child['id'])>
                        — {{ $child['name'] }}
                    </option>
                @endforeach
            @endforeach
        </select>
    </details>

    @if ($priceMax > 0)
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Qiymət</span>
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-price-range">
                <label class="sf-price-field">
                    <span>Ən az</span>
                    <span class="sf-price-field__box">
                        <input type="number" name="price_min" min="0" max="{{ ceil($priceMax) }}" step="1"
                               value="{{ $selected['price_min'] }}" placeholder="{{ floor($priceMin) }}">
                        <span>₼</span>
                    </span>
                </label>
                <span class="sf-price-range__dash" aria-hidden="true">—</span>
                <label class="sf-price-field">
                    <span>Ən çox</span>
                    <span class="sf-price-field__box">
                        <input type="number" name="price_max" min="0" max="{{ ceil($priceMax) }}" step="1"
                               value="{{ $selected['price_max'] }}" placeholder="{{ ceil($priceMax) }}">
                        <span>₼</span>
                    </span>
                </label>
            </div>
            <button type="submit" class="sf-price-apply">Tətbiq et</button>
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
            $extraValues = collect($values)->skip(10)
                ->reject(fn ($value) => in_array((string) $value, $picked, true))->count();
        @endphp
        @if ($values !== [])
            <details class="sf-filter-card" open>
                <summary class="sf-filter-head">
                    <span>{{ $title }}</span>
                    <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
                </summary>
                <div class="sf-filter-options sf-filter-options--check">
                    @foreach ($values as $index => $value)
                        <label class="sf-filter-option" @if ($index >= 10 && ! in_array((string) $value, $picked, true)) data-sf-extra hidden @endif>
                            <input type="checkbox" name="filters[{{ $filter['slug'] ?? $filter['id'] }}][]" value="{{ $value }}"
                                   @checked(in_array((string) $value, $picked, true)) data-sf-auto-submit>
                            <span class="sf-filter-option__text">{{ $value }}</span>
                        </label>
                    @endforeach
                </div>
                @if ($extraValues > 0)
                    <button type="button" class="sf-filter-more" data-sf-show-more
                            data-more-label="Daha çox göstər (+{{ $extraValues }})"
                            data-less-label="Daha az göstər">Daha çox göstər (+{{ $extraValues }})</button>
                @endif
            </details>
        @endif
    @endforeach

    @if (! empty($facets['brands']))
        @php
            $extraBrands = collect($facets['brands'])->skip(10)
                ->reject(fn ($brand) => in_array((string) ($brand['slug'] ?? $brand['id']), $selectedBrands, true))->count();
        @endphp
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Brendlər</span>
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-filter-options">
                @foreach ($facets['brands'] as $index => $brand)
                    @php $brandValue = (string) ($brand['slug'] ?? $brand['id']); @endphp
                    <label class="sf-filter-option" @if ($index >= 10 && ! in_array($brandValue, $selectedBrands, true)) data-sf-extra hidden @endif>
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
                        data-more-label="Daha çox göstər (+{{ $extraBrands }})"
                        data-less-label="Daha az göstər">Daha çox göstər (+{{ $extraBrands }})</button>
            @endif
        </details>
    @endif

    @if (! empty($facets['colors']))
        @php
            $extraColors = collect($facets['colors'])->skip(10)
                ->reject(fn ($color) => in_array((string) ($color['slug'] ?? $color['id']), $selectedColors, true))->count();
        @endphp
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Rənglər</span>
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-filter-options">
                @foreach ($facets['colors'] as $index => $color)
                    @php $colorValue = (string) ($color['slug'] ?? $color['id']); @endphp
                    <label class="sf-filter-option" @if ($index >= 10 && ! in_array($colorValue, $selectedColors, true)) data-sf-extra hidden @endif>
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
                        data-more-label="Daha çox göstər (+{{ $extraColors }})"
                        data-less-label="Daha az göstər">Daha çox göstər (+{{ $extraColors }})</button>
            @endif
        </details>
    @endif

    @if (! empty($facets['sizes']))
        @php
            $extraSizes = collect($facets['sizes'])->skip(12)
                ->reject(fn ($size) => in_array((string) ($size['slug'] ?? $size['id']), $selectedSizes, true))->count();
        @endphp
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Ölçülər</span>
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-size-grid">
                @foreach ($facets['sizes'] as $index => $size)
                    @php $sizeValue = (string) ($size['slug'] ?? $size['id']); @endphp
                    <label class="sf-size-option" @if ($index >= 12 && ! in_array($sizeValue, $selectedSizes, true)) data-sf-extra hidden @endif>
                        <input type="checkbox" name="sizes[]" value="{{ $sizeValue }}"
                               @checked(in_array($sizeValue, $selectedSizes, true)) data-sf-auto-submit>
                        <span>{{ $size['name'] ?? '—' }}</span>
                        <span class="sf-size-option__count">{{ $size['products_count'] ?? 0 }}</span>
                    </label>
                @endforeach
            </div>
            @if ($extraSizes > 0)
                <button type="button" class="sf-filter-more" data-sf-show-more
                        data-more-label="Daha çox göstər (+{{ $extraSizes }})"
                        data-less-label="Daha az göstər">Daha çox göstər (+{{ $extraSizes }})</button>
            @endif
        </details>
    @endif

    <div class="sf-filter-actions">
        <button type="submit" class="sf-btn sf-btn--primary">Nəticələri göstər</button>
        <a href="/shop" class="sf-btn sf-btn--ghost">Sıfırla</a>
    </div>
</form>
