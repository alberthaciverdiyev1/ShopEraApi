@php
    $search = $query['search'] ?? '';
    $activeCategory = $query['category'] ?? '';
    $total = (int) ($meta['total'] ?? 0);
@endphp

<x-storefront.layouts.app
    :title="'Bloq — ShopEra'"
    :description="'ShopEra bloqu: xəbərlər, məsləhətlər və bələdçilər.'"
    :categories="$categories">

    <x-storefront.breadcrumb title="Bloq" />

    <div class="container sf-blog">
        <header class="sf-blog__head">
            <h1>Bloq</h1>
            <p>{{ number_format($total) }} məqalə</p>
        </header>

        <div class="sf-blog__toolbar">
            <div class="sf-blog__pills">
                <a href="/blog" class="sf-pill @if (! $activeCategory && ! $search) is-active @endif">Hamısı</a>
                @foreach ($blogCategories as $category)
                    <a href="/blog?category={{ urlencode($category['name']) }}"
                       class="sf-pill @if ($activeCategory === $category['name']) is-active @endif">
                        {{ $category['name'] }}
                        @if (! empty($category['count']))
                            <span>{{ $category['count'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            <form class="sf-blog__search" action="/blog" method="GET">
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                @endif
                <input type="search" name="search" value="{{ $search }}" placeholder="Məqalə axtar…">
                <button type="submit" aria-label="Axtar"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>

        @if (count($items) > 0)
            <div class="sf-blog-grid">
                @foreach ($items as $article)
                    <x-storefront.blog-card :article="$article" />
                @endforeach
            </div>

            <x-storefront.pagination :meta="$meta" />
        @else
            <div class="sf-empty">
                <i class="fa-regular fa-newspaper"></i>
                <h2>Məqalə tapılmadı</h2>
                <p>Axtarış sözünü və ya kateqoriyanı dəyişdirin.</p>
                <a href="/blog" class="sf-btn sf-btn--primary">Bütün məqalələr</a>
            </div>
        @endif
    </div>
</x-storefront.layouts.app>
