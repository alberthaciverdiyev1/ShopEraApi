<x-storefront.layouts.app
    :title="'Tez-tez verilən suallar — ShopEra'"
    :description="'ShopEra ilə bağlı tez-tez verilən suallar və cavablar.'"
    :categories="$categories">

    <x-storefront.breadcrumb title="Tez-tez verilən suallar" />

    <div class="container sf-faq-page">
        <header class="sf-blog__head">
            <h1>Tez-tez verilən suallar</h1>
            <p>Bilmək istədikləriniz</p>
        </header>

        @if (count($faqs) > 0)
            <div class="sf-faq" data-sf-faq>
                @foreach ($faqs as $index => $faq)
                    <div class="sf-faq__item @if ($index === 0) is-open @endif">
                        <button type="button" class="sf-faq__question" data-sf-faq-toggle aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                            <span>{{ $faq['title'] }}</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="sf-faq__answer" @if ($index !== 0) hidden @endif>
                            <p>{{ $faq['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="sf-empty">
                <i class="fa-regular fa-circle-question"></i>
                <h2>Hələ sual yoxdur</h2>
            </div>
        @endif
    </div>
</x-storefront.layouts.app>
