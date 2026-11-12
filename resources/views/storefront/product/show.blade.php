@php
    $title = $product['title'] ?? 'Elan';
    $description = \Illuminate\Support\Str::limit(trim(strip_tags((string) ($product['description'] ?? ''))), 155);
    $parentCategory = collect($product['characteristics'] ?? [])->firstWhere('label', 'Kateqoriya');
@endphp

<x-storefront.layouts.app
    :title="$title.' — ShopEra'"
    :description="$description"
    :categories="$categories">

    <x-storefront.breadcrumb :title="$title" :parent="$parentCategory['value'] ?? 'Elanlar'" />

    <div class="container sf-product">
        <div class="sf-product__top">
            <div class="sf-product__media">
                <x-storefront.product.gallery :images="$product['gallery'] ?? []" :title="$title" />
            </div>

            <div class="sf-product__info">
                <x-storefront.product.summary :product="$product" />
            </div>
        </div>

        <x-storefront.product.tabs :product="$product" />

        @if (count($similar) > 0)
            <section class="sf-product-similar">
                <div class="sf-section-head">
                    <h2>Bənzər elanlar</h2>
                    @if (! empty($product['category']['id']))
                        <a href="/shop?category={{ $product['category']['id'] }}">Hamısına bax <i class="fa-solid fa-arrow-right"></i></a>
                    @endif
                </div>

                <div class="sf-product-grid">
                    @foreach ($similar as $item)
                        <x-storefront.product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <x-storefront.product.chat :product="$product" />
</x-storefront.layouts.app>
