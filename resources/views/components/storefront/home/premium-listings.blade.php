@props(['products' => []])

@if (! empty($products))
    <section class="sf-products-section sf-premium-section">
        <div class="container">
            <div class="sf-section-head">
                <div class="section-title">
                    <h2 class="title">Premium elanlar</h2>
                </div>
                <a class="sf-section-head__link" href="/shop">Son elanlar</a>
            </div>

            <div class="sf-product-grid">
                @foreach ($products as $product)
                    <div class="sf-product-grid__item">
                        <x-storefront.product-card :product="$product" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
