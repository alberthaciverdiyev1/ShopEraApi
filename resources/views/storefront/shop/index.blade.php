@php
    $activeCategory = collect($categories)->flatMap(function ($category) {
        $children = $category['children'] ?? [];

        if ($children instanceof \Illuminate\Http\Resources\Json\ResourceCollection) {
            $children = $children->resolve();
        } else {
            $children = (array) $children;
        }

        return array_merge([$category], $children);
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
@endphp

<x-storefront.layouts.app
    :title="$pageTitle.' — ShopEra'"
    :description="'ShopEra elanları: '.$pageTitle"
    :categories="$categories">

    <x-storefront.breadcrumb title="Elanlar" />

    <div class="container sf-shop">
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

                <button type="button" class="sf-shop__filter-toggle" data-sf-mobile-filters>
                    <i class="fa-regular fa-sliders"></i> Filtrlər
                </button>
            </div>
        </header>

        <div class="sf-shop__layout">
            <aside class="sf-shop__sidebar" data-sf-sidebar>
                <button type="button" class="sf-shop__sidebar-close" data-sf-sidebar-close aria-label="Bağla">
                    <i class="fa-regular fa-xmark"></i>
                </button>

                <x-storefront.shop.filters
                    :categories="$categories"
                    :facets="$facets"
                    :dynamic-filters="$dynamicFilters"
                    :category-id="$categoryId"
                    :sorts="$sorts"
                    :query="$query" />
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
