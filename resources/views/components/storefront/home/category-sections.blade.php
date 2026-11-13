@props(['sections' => []])

@if (! empty($sections))
    @foreach ($sections as $section)
        <section class="sf-products-section sf-home-category">
            <div class="container">
                <div class="sf-section-head">
                    <div class="section-title">
                        <h2 class="title">
                            {{ $section['name'] }}
                            <span class="sf-section-head__count">({{ $section['count'] }})</span>
                        </h2>
                    </div>
                    <a class="sf-section-head__link"
                       href="/shop?category={{ $section['slug'] ?? $section['id'] }}">
                        Hamısını gör <i class="fa-regular fa-arrow-right"></i>
                    </a>
                </div>

                <div class="sf-product-grid">
                    @foreach ($section['products'] as $product)
                        <div class="sf-product-grid__item">
                            <x-storefront.product-card :product="$product" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach
@endif
