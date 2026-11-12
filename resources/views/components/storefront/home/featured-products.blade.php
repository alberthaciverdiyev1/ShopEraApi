@props(['products' => []])

@if (! empty($products))
    <section class="sf-products-section sf-featured-section">
        <div class="container">
            <div class="sf-section-head sf-section-head--center">
                <div class="section-title">
                    <h2 class="title">Seçilmiş elanlar</h2>
                </div>
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
