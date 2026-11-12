<x-storefront.layouts.app
    :title="'Haqqımızda — ShopEra'"
    :description="'ShopEra — Azərbaycanda elan və mağaza platforması.'"
    :categories="$categories">

    <x-storefront.breadcrumb title="Haqqımızda" />

    <div class="container sf-content-page">
        <article class="sf-prose">
            @if ($html)
                {!! $html !!}
            @else
                <p>Məzmun hələ əlavə edilməyib.</p>
            @endif
        </article>

        @if (count($reviews) > 0)
            <section class="sf-testimonials">
                <div class="sf-section-head">
                    <h2>Müştərilərimiz nə deyir</h2>
                </div>

                <div class="sf-testimonial-grid">
                    @foreach ($reviews as $review)
                        <figure class="sf-testimonial">
                            <div class="sf-testimonial__stars">
                                @for ($star = 1; $star <= 5; $star++)
                                    <i class="fa-{{ $star <= (int) ($review['rate'] ?? 0) ? 'solid' : 'regular' }} fa-star"></i>
                                @endfor
                            </div>
                            @if (! empty($review['comment']))
                                <blockquote>{{ $review['comment'] }}</blockquote>
                            @endif
                            <figcaption>
                                <strong>{{ $review['user']['name'] ?? 'İstifadəçi' }}</strong>
                                @if (! empty($review['product']['title']))
                                    <small>{{ $review['product']['title'] }}</small>
                                @endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-storefront.layouts.app>
