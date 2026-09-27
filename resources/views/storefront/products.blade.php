@extends('storefront.layout')
@section('content')
<section class="page-hero">
    <div class="shell">
        <h1>{{ $activeCategory ? \Illuminate\Support\Str::ucfirst(trim((string) (is_array($activeCategory->name) ? ($activeCategory->name[app()->getLocale()] ?? $activeCategory->name['az'] ?? reset($activeCategory->name)) : $activeCategory->name))) : 'Məhsullar' }}</h1>
        <p>Məhsulları rahat araşdır, sonra sifariş üçün tətbiqə keç.</p>
        <form class="filter-bar" action="{{ route('storefront.products') }}" method="get">
            @if($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory->id }}">@endif
            <input name="search" value="{{ $search }}" placeholder="Məhsul axtar..." aria-label="Məhsul axtar">
            <button class="pill-button" type="submit">Axtar</button>
        </form>
        <div class="cat-tabs">
            <a class="cat-tab {{ !$activeCategory ? 'active' : '' }}" href="{{ route('storefront.products') }}">Hamısı</a>
            @foreach($categories as $category)
                @php $name = \Illuminate\Support\Str::ucfirst(trim((string) (is_array($category->name) ? ($category->name[app()->getLocale()] ?? $category->name['az'] ?? reset($category->name)) : $category->name))); @endphp
                <a class="cat-tab {{ $activeCategory?->id === $category->id ? 'active' : '' }}" href="{{ route('storefront.products', ['category' => $category->id]) }}">{{ $name }}</a>
            @endforeach
        </div>
    </div>
</section>
<section class="section" style="padding-top:4px;border:0">
    <div class="shell">
        @if($products->isEmpty())
            <div class="info-card">
                <h2>Nəticə tapılmadı</h2>
                <p>Başqa axtarış sözü və ya kateqoriya ilə yenidən yoxla.</p>
            </div>
        @else
            <div class="product-grid">
                @foreach($products as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
            <div class="pagination">{{ $products->links('vendor.pagination.storefront') }}</div>
        @endif
    </div>
</section>
@endsection
