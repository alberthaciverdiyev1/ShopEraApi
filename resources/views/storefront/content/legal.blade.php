<x-storefront.layouts.app
    :title="$pageTitle.' — ShopEra'"
    :description="$pageTitle"
    :categories="$categories">

    <x-storefront.breadcrumb :title="$pageTitle" />

    <div class="container sf-content-page">
        <article class="sf-prose">
            @if ($html)
                {!! $html !!}
            @else
                <p>Bu səhifə hələ mövcud deyil.</p>
            @endif
        </article>
    </div>
</x-storefront.layouts.app>
