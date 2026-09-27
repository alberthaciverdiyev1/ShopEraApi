@extends('storefront.layout')

@section('content')
<section class="page-hero">
    <div class="shell">
        <h1>Kateqoriyalar</h1>
        <p>Mobil tətbiqdəki kateqoriya quruluşuna uyğun məhsul bölmələri.</p>
    </div>
</section>

<section class="section" style="padding-top: 10px">
    <div class="shell">
        <div class="category-grid">
            @foreach($categories as $category)
                @php
                    $name = is_array($category->name) ? ($category->name[app()->getLocale()] ?? $category->name['az'] ?? reset($category->name)) : $category->name;
                    $name = \Illuminate\Support\Str::ucfirst(trim((string) $name));
                @endphp
                <a class="category-card" href="{{ route('storefront.products', ['category' => $category->id]) }}">
                    @if($category->image)<img class="category-thumb" src="{{ $category->image }}" alt="{{ $name }}">@endif
                    <div>
                        <h3>{{ $name }}</h3>
                        <span>{{ (int) $category->products_count }} məhsul →</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endsection
