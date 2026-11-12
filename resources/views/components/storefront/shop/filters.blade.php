@props([
    'categories' => [],
    'facets' => [],
    'dynamicFilters' => [],
    'categoryId' => null,
    'sorts' => [],
    'query' => [],
])

@php
    $selected = [
        'search' => $query['search'] ?? '',
        'brand' => (int) ($query['brand'] ?? 0),
        'color' => (int) ($query['color'] ?? 0),
        'size' => (int) ($query['size'] ?? 0),
        'price_max' => $query['price_max'] ?? '',
        'sort' => $query['sort'] ?? 'created_at:desc',
    ];
    $selectedDynamic = $query['filters'] ?? [];
    $priceMin = (float) ($facets['price']['min'] ?? 0);
    $priceMax = (float) ($facets['price']['max'] ?? 0);

    $sorts = $sorts ?: ['created_at:desc'];
    $sortLabels = [
        'created_at:desc' => 'Ən yeni',
        'price:asc' => 'Qiymət: əvvəl ucuz',
        'price:desc' => 'Qiymət: əvvəl baha',
        'sales_count:desc' => 'Ən çox satılan',
    ];

    $findName = function (array $list, int $id) {
        foreach ($list as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
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

    $buildUrl = function (array $dropKeys = [], array $dropFilterIds = []) use ($query) {
        $params = collect($query)->except('page')->all();

        foreach ($dropKeys as $key) {
            unset($params[$key]);
        }

        foreach ($dropFilterIds as $filterId) {
            unset($params['filters'][$filterId]);

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

    if ($activeCategoryName) {
        $chips[] = ['label' => $activeCategoryName, 'url' => $buildUrl(['category'])];
    }

    if ($selected['brand']) {
        $chips[] = ['label' => $findName($facets['brands'] ?? [], $selected['brand']) ?? 'Brend', 'url' => $buildUrl(['brand'])];
    }

    if ($selected['color']) {
        $chips[] = ['label' => $findName($facets['colors'] ?? [], $selected['color']) ?? 'Rəng', 'url' => $buildUrl(['color'])];
    }

    if ($selected['size']) {
        $chips[] = ['label' => $findName($facets['sizes'] ?? [], $selected['size']) ?? 'Ölçü', 'url' => $buildUrl(['size'])];
    }

    if ($selected['price_max'] !== '' && $selected['price_max'] !== null) {
        $chips[] = ['label' => '≤ '.number_format((float) $selected['price_max'], 0).' ₼', 'url' => $buildUrl(['price_max'])];
    }

    foreach ($dynamicFilters as $filter) {
        foreach ((array) ($selectedDynamic[$filter['id']] ?? []) as $value) {
            $chips[] = ['label' => (string) $value, 'url' => $buildUrl([], [$filter['id']])];
        }
    }

    $activeCount = count($chips);
    $customPrice = $selected['price_max'] !== '' && $selected['price_max'] !== null;

    $priceUrl = function ($limit) use ($query) {
        $params = collect($query)->except('page')->all();

        if ($limit === null) {
            unset($params['price_max']);
        } else {
            $params['price_max'] = $limit;
        }

        $qs = http_build_query($params);

        return $qs === '' ? '/shop' : '/shop?'.$qs;
    };
@endphp

<form class="sf-shop-filters" action="/shop" method="GET" data-sf-filter-form>
    <input type="hidden" name="page" value="1">
    {{-- Sorting lives in the page toolbar; keep the chosen value when filters are applied. --}}
    <input type="hidden" name="sort" value="{{ $selected['sort'] }}">

    <div class="sf-filters-head">
        <div class="sf-filters-head__title">
            <i class="fa-regular fa-sliders"></i>
            Filtrlər
            @if ($activeCount)
                <span class="sf-filters-head__badge">{{ $activeCount }}</span>
            @endif
        </div>
        @if ($activeCount)
            <a href="/shop" class="sf-filters-head__reset">Sıfırla</a>
        @endif
    </div>

    <div class="sf-filter-search">
        <i class="fa-regular fa-magnifying-glass"></i>
        <input id="sf-search" type="search" name="search" value="{{ $selected['search'] }}" placeholder="Elan axtar…" aria-label="Axtarış">
        <button type="submit" aria-label="Axtar">Axtar</button>
    </div>

    @if ($chips)
        <div class="sf-filter-chips" aria-label="Aktiv filtrlər">
            @foreach ($chips as $chip)
                <a href="{{ $chip['url'] }}" class="sf-filter-chip">
                    {{ $chip['label'] }}
                    <i class="fa-solid fa-xmark"></i>
                </a>
            @endforeach
            <a href="/shop" class="sf-filter-chip sf-filter-chip--clear">Hamısını təmizlə</a>
        </div>
    @endif

    <details class="sf-filter-card" open>
        <summary class="sf-filter-head">
            <span>Kateqoriyalar</span>
            @if ($activeCategoryName)
                <span class="sf-filter-head__value">{{ $activeCategoryName }}</span>
            @endif
            <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
        </summary>
        <select name="category" data-sf-auto-submit>
            <option value="">Bütün kateqoriyalar</option>
            @foreach ($categories as $category)
                <option value="{{ $category['id'] }}" @selected((int) $categoryId === (int) $category['id'])>
                    {{ $category['name'] }}
                </option>
                @foreach ($category['children'] ?? [] as $child)
                    <option value="{{ $child['id'] }}" @selected((int) $categoryId === (int) $child['id'])>
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
                @if ($customPrice)
                    <span class="sf-filter-head__value">≤ {{ number_format((float) $selected['price_max'], 0) }} ₼</span>
                @endif
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-price-quick">
                @foreach (array_values(array_filter([50, 100, 250, 500, 1000], fn ($limit) => $limit < $priceMax)) as $limit)
                    <a href="{{ $priceUrl($limit) }}"
                       class="sf-price-chip {{ (string) $limit === (string) $selected['price_max'] ? 'is-active' : '' }}">
                        ≤ {{ number_format($limit, 0) }} ₼
                    </a>
                @endforeach
                @if ($customPrice)
                    <a href="{{ $priceUrl(null) }}" class="sf-price-chip">Limiti sil</a>
                @endif
            </div>
            <label class="sf-price-input" for="sf-price-max">
                <span>Öz limitiniz</span>
                <span class="sf-price-input__field">
                    <input id="sf-price-max" type="number" name="price_max" min="{{ floor($priceMin) }}" max="{{ ceil($priceMax) }}"
                           step="1" value="{{ $selected['price_max'] }}" placeholder="{{ ceil($priceMax) }}" data-sf-auto-submit>
                    <span>₼</span>
                </span>
            </label>
            <p class="sf-price-hint">Aralıq: {{ number_format($priceMin, 0) }} – {{ number_format($priceMax, 0) }} ₼</p>
        </details>
    @endif

    @if (! empty($facets['brands']))
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Brendlər</span>
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-filter-options">
                <label class="sf-filter-option">
                    <input type="radio" name="brand" value="" @checked(! $selected['brand']) data-sf-auto-submit>
                    <span class="sf-filter-option__text">Hamısı</span>
                </label>
                @foreach (array_slice($facets['brands'], 0, 12) as $brand)
                    <label class="sf-filter-option">
                        <input type="radio" name="brand" value="{{ $brand['id'] }}" @checked($selected['brand'] === (int) $brand['id']) data-sf-auto-submit>
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
        </details>
    @endif

    @if (! empty($facets['colors']))
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Rənglər</span>
                @if ($selected['color'])
                    <span class="sf-filter-head__value">{{ $findName($facets['colors'], $selected['color']) ?? 'Seçilib' }}</span>
                @endif
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-filter-options">
                <label class="sf-filter-option">
                    <input type="radio" name="color" value="" @checked(! $selected['color']) data-sf-auto-submit>
                    <span class="sf-filter-option__text">Hamısı</span>
                </label>
                @foreach (array_slice($facets['colors'], 0, 12) as $color)
                    <label class="sf-filter-option">
                        <input type="radio" name="color" value="{{ $color['id'] }}" @checked($selected['color'] === (int) $color['id']) data-sf-auto-submit>
                        <span class="sf-filter-option__text">
                            <span class="sf-swatch" style="background-color: {{ $color['hex'] ?? '#cccccc' }}"></span>
                            {{ $color['name'] ?? '—' }}
                        </span>
                        <span class="sf-filter-option__count">{{ $color['products_count'] ?? 0 }}</span>
                    </label>
                @endforeach
            </div>
        </details>
    @endif

    @if (! empty($facets['sizes']))
        <details class="sf-filter-card" open>
            <summary class="sf-filter-head">
                <span>Ölçülər</span>
                @if ($selected['size'])
                    <span class="sf-filter-head__value">{{ $findName($facets['sizes'], $selected['size']) ?? 'Seçilib' }}</span>
                @endif
                <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
            </summary>
            <div class="sf-filter-options">
                <label class="sf-filter-option">
                    <input type="radio" name="size" value="" @checked(! $selected['size']) data-sf-auto-submit>
                    <span class="sf-filter-option__text">Hamısı</span>
                </label>
                @foreach (array_slice($facets['sizes'], 0, 12) as $size)
                    <label class="sf-filter-option">
                        <input type="radio" name="size" value="{{ $size['id'] }}" @checked($selected['size'] === (int) $size['id']) data-sf-auto-submit>
                        <span class="sf-filter-option__text">{{ $size['name'] ?? '—' }}</span>
                        <span class="sf-filter-option__count">{{ $size['products_count'] ?? 0 }}</span>
                    </label>
                @endforeach
            </div>
        </details>
    @endif

    @foreach ($dynamicFilters as $filter)
        @php
            $title = is_array($filter['title'] ?? null)
                ? ($filter['title']['az'] ?? reset($filter['title']))
                : ($filter['title'] ?? 'Parametr');
            $values = ! empty($filter['options']) ? $filter['options'] : ($filter['values'] ?? []);
            $values = array_values(array_unique(array_filter($values)));
            $picked = array_map('strval', (array) ($selectedDynamic[$filter['id']] ?? []));
        @endphp
        @if ($values !== [])
            <details class="sf-filter-card" open>
                <summary class="sf-filter-head">
                    <span>{{ $title }}</span>
                    @if ($picked)
                        <span class="sf-filter-head__value">{{ count($picked) }} seçilib</span>
                    @endif
                    <i class="fa-solid fa-chevron-down sf-filter-head__chevron"></i>
                </summary>
                <div class="sf-filter-options sf-filter-options--check">
                    @foreach (array_slice($values, 0, 12) as $value)
                        <label class="sf-filter-option">
                            <input type="checkbox" name="filters[{{ $filter['id'] }}][]" value="{{ $value }}"
                                   @checked(in_array((string) $value, $picked, true)) data-sf-auto-submit>
                            <span class="sf-filter-option__text">{{ $value }}</span>
                        </label>
                    @endforeach
                </div>
            </details>
        @endif
    @endforeach

    <div class="sf-filter-actions">
        <button type="submit" class="sf-btn sf-btn--primary">Tətbiq et</button>
        <a href="/shop" class="sf-btn sf-btn--ghost">Sıfırla</a>
    </div>
</form>
