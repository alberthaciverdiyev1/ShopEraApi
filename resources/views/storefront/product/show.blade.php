@php
    $title = $product['title'] ?? 'Elan';
    $description = \Illuminate\Support\Str::limit(trim(strip_tags((string) ($product['description'] ?? ''))), 155);
    $parentCategory = collect($product['characteristics'] ?? [])->firstWhere('label', 'Kateqoriya');
    $parentUrl = $parentCategory['link'] ?? null;
@endphp

<x-storefront.layouts.app
    :title="$title.' — ShopEra'"
    :description="$description"
    :categories="$categories">

    <x-storefront.breadcrumb :title="$title" :parent="$parentCategory['value'] ?? 'Elanlar'" :parent-url="$parentUrl" :show-category-action="false" />

    <div class="container sf-product">
        <div class="sf-product__top">
            <div class="sf-product__media">
                <x-storefront.product.gallery :images="$product['gallery'] ?? []" :title="$title" :product="$product" />
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
                        <a href="/shop?category={{ $product['category']['slug'] ?? $product['category']['id'] }}">Hamısına bax <i class="fa-solid fa-arrow-right"></i></a>
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

    <!-- Mobile Sticky Action Bar -->
    <div class="sf-product-sticky-bottom" data-sf-sticky-bar>
        <div class="sf-product-sticky-bottom__price">
            <span class="sf-product-sticky-bottom__current" data-sf-price-current>{{ number_format((float) $product['current_price'], 2) }} ₼</span>
            <span class="sf-product-sticky-bottom__old" data-sf-price-old @if (empty($product['has_discount'])) hidden @endif>{{ number_format((float) $product['original_price'], 2) }} ₼</span>
        </div>
        <div class="sf-product-sticky-bottom__actions">
            <button type="button" class="sf-btn sf-btn--primary sf-product-sticky-bottom__btn" data-sf-contact-reveal>
                <i class="fa-solid fa-phone"></i> Nömrəni göstər
            </button>
            @auth
                <button type="button" class="sf-btn sf-btn--ghost sf-product-sticky-bottom__chat" data-sf-lchat-open aria-label="Satıcıya yaz">
                    <i class="fa-solid fa-comment-dots"></i>
                </button>
            @else
                <a href="/login" class="sf-btn sf-btn--ghost sf-product-sticky-bottom__chat" aria-label="Satıcıya yaz">
                    <i class="fa-solid fa-comment-dots"></i>
                </a>
            @endauth
        </div>
    </div>

    <x-storefront.product.chat :product="$product" />
</x-storefront.layouts.app>
