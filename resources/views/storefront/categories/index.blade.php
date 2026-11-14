<x-storefront.layouts.app
    :title="'Kateqoriyalar — ShopEra'"
    :description="'ShopEra kateqoriyaları: bütün elan bölmələri.'"
    :categories="$categories">

    <x-storefront.breadcrumb title="Kateqoriyalar" />

    <div class="container sf-catalog">
        <header class="sf-blog__head">
            <h1>Kateqoriyalar</h1>
            <p>{{ count($categories) }} kateqoriya</p>
        </header>

        <div class="sf-catalog-grid">
            @foreach ($categories as $category)
                <a class="sf-catalog-card" href="/shop?category={{ $category['slug'] ?? $category['id'] }}">
                    <div class="sf-catalog-card__image">
                        @if (! empty($category['image']))
                            <img src="{{ $category['image'] }}" alt="{{ $category['name'] }}" loading="lazy">
                        @else
                            <i class="fa-regular fa-grid-2"></i>
                        @endif
                    </div>
                    <div class="sf-catalog-card__body">
                        <span class="sf-catalog-card__name">{{ $category['name'] }}</span>
                        @if (! empty($category['products_count']))
                            <span class="sf-catalog-card__count">{{ (int) $category['products_count'] }} elan</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-storefront.layouts.app>
