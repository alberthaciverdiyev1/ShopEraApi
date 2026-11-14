@php
    $currentCategory = $currentCategory ?? null;
    $parentCategory = $parentCategory ?? null;
    $currentName = $currentCategory['name'] ?? null;
    $currentSlug = $currentCategory['slug'] ?? $currentCategory['id'] ?? null;
@endphp

<x-storefront.layouts.app
    :title="($currentName ?? 'Kateqoriyalar').' — ShopEra'"
    :description="'ShopEra kateqoriyaları: bütün elan bölmələri.'"
    :categories="$categories">

    <x-storefront.breadcrumb :title="$currentName ?? 'Kateqoriyalar'" />

    <div class="container sf-catalog">
        <header class="sf-blog__head">
            @if ($currentName)
                <div class="sf-catalog__crumbs">
                    <a href="{{ route('storefront.categories') }}">Kateqoriyalar</a>
                    @if ($parentCategory)
                        <span>/</span>
                        <a href="{{ route('storefront.categories', ['category' => $parentCategory['slug'] ?? $parentCategory['id']]) }}">
                            {{ $parentCategory['name'] }}
                        </a>
                    @endif
                </div>
                <h1>{{ $currentName }}</h1>
                <p>{{ count($categories) }} alt kateqoriya</p>
            @else
                <h1>Kateqoriyalar</h1>
                <p>{{ count($categories) }} kateqoriya</p>
            @endif
        </header>

        @if ($currentName)
            <a class="sf-catalog__all" href="/shop?category={{ $currentSlug }}">
                <i class="fa-solid fa-list"></i> {{ $currentName }} bölməsindəki bütün elanlar
            </a>
        @endif

        <div class="sf-catalog-grid">
            @foreach ($categories as $category)
                @php
                    $slug = $category['slug'] ?? $category['id'];
                    $hasChildren = ! empty($category['children']);
                    $href = $hasChildren
                        ? route('storefront.categories', ['category' => $slug])
                        : '/shop?category='.$slug;
                @endphp
                <a class="sf-catalog-card" href="{{ $href }}">
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
                        @if ($hasChildren)
                            <span class="sf-catalog-card__count">{{ count($category['children']) }} alt kateqoriya</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-storefront.layouts.app>
