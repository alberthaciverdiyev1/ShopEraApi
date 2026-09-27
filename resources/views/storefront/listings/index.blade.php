@extends('storefront.layout')
@section('content')
@include('storefront.listings.partials.style')

<section class="page-hero lst-ink">
    <div class="shell">
        <h1>Elanlar</h1>
        <p>Nəqliyyat, daşınmaz əmlak və digər bölmələr — eyni elanlar tətbiqdə də var.</p>
    </div>
</section>

<section class="section lst-ink" style="padding-top:4px;border:0">
    <div class="shell">
        <div class="lst-sections">
            @foreach($sections as $section)
                <a class="lst-section-card {{ $section->template === 'property' ? 'property' : '' }}"
                   href="{{ route('storefront.listings.section', ['section' => $section->key]) }}">
                    @if($section->banner_path)
                        <img src="{{ Storage::disk('public')->url($section->banner_path) }}" alt="">
                    @endif
                    <span>{{ $section->name }}</span>
                    <small>{{ $section->listings_count }} elan</small>
                </a>
            @endforeach
        </div>
    </div>
</section>

@if($latest->isNotEmpty())
<section class="section lst-ink">
    <div class="shell">
        <div class="section-head"><h2>Son elanlar</h2></div>
        <div class="lst-grid">
            @foreach($latest as $card)
                @include('storefront.listings.partials.card', ['card' => $card])
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
