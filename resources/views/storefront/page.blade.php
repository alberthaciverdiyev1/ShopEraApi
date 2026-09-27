@extends('storefront.layout')

@section('content')
<section class="page-hero">
    <div class="shell">
        <p style="margin:0 0 8px;color:var(--brand);font-weight:850">{{ $eyebrow }}</p>
        <h1>{{ $title }}</h1>
    </div>
</section>

<section class="shell article">
    {!! $content !!}
</section>
@endsection
