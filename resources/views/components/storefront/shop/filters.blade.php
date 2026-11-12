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
    $priceMax = (float) ($facets['price']['max'] ?? 0);

    $sortLabels = [
        'created_at:desc' => 'Ən yeni',
        'price:asc' => 'Qiymət: əvvəl ucuz',
        'price:desc' => 'Qiymət: əvvəl baha',
        'sales_count:desc' => 'Ən çox satılan',
    ];

    $chip = fn (string $label, string $name, $value) => ['label' => $label, 'name' => $name, 'value' => $value];
@endphp

<form class="sf-shop-filters" action="/shop" method="GET" data-sf-filter-form>
    <input type="hidden" name="page" value="1">

    <div class="sf-filter-card">
        <label class="sf-filter-label" for="sf-search">Axtarış</label>
        <div class="sf-filter-search">
            <input id="sf-search" type="search" name="search" value="{{ $selected['search'] }}" placeholder="Elan axtar…">
            <button type="submit" aria-label="Axtar"><i class="fa-regular fa-magnifying-glass"></i></button>
        </div>
    </div>

    <div class="sf-filter-card">
        <label class="sf-filter-label" for="sf-sort">Sıralama</label>
        <select id="sf-sort" name="sort" data-sf-auto-submit>
            @foreach ($sorts as $sort)
                <option value="{{ $sort }}" @selected($selected['sort'] === $sort)>{{ $sortLabels[$sort] ?? $sort }}</option>
            @endforeach
        </select>
    </div>

    <div class="sf-filter-card">
        <h3 class="sf-filter-label">Kateqoriyalar</h3>
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
    </div>

    @if ($priceMax > 0)
        <div class="sf-filter-card">
            <h3 class="sf-filter-label">Qiymət aralığı</h3>
            <div class="sf-price-row">
                <span>{{ number_format((float) ($facets['price']['min'] ?? 0), 0) }} ₼</span>
                <input type="number" name="price_max" min="0" max="{{ ceil($priceMax) }}" step="1"
                       value="{{ $selected['price_max'] }}" placeholder="{{ ceil($priceMax) }}" data-sf-auto-submit>
                <span>{{ number_format($priceMax, 0) }} ₼</span>
            </div>
        </div>
    @endif

    @if (! empty($facets['brands']))
        <div class="sf-filter-card">
            <h3 class="sf-filter-label">Brendlər</h3>
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
        </div>
    @endif

    @if (! empty($facets['colors']))
        <div class="sf-filter-card">
            <h3 class="sf-filter-label">Rənglər</h3>
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
        </div>
    @endif

    @if (! empty($facets['sizes']))
        <div class="sf-filter-card">
            <h3 class="sf-filter-label">Ölçülər</h3>
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
        </div>
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
            <div class="sf-filter-card">
                <h3 class="sf-filter-label">{{ $title }}</h3>
                <div class="sf-filter-options">
                    @foreach (array_slice($values, 0, 12) as $value)
                        <label class="sf-filter-option">
                            <input type="checkbox" name="filters[{{ $filter['id'] }}][]" value="{{ $value }}"
                                   @checked(in_array((string) $value, $picked, true)) data-sf-auto-submit>
                            <span class="sf-filter-option__text">{{ $value }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    <div class="sf-filter-actions">
        <button type="submit" class="sf-btn sf-btn--primary">Tətbiq et</button>
        <a href="/shop" class="sf-btn sf-btn--ghost">Sıfırla</a>
    </div>
</form>
