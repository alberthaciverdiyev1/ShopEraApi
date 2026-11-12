@props(['article'])

@php
    $url = '/blog/'.($article['slug'] ?? '');
    $image = $article['image'] ?? '/assets/images/blog/blogThumb2_1.jpg';
    $date = ! empty($article['published_at']) ? \Illuminate\Support\Carbon::parse($article['published_at']) : null;
@endphp

<article class="sf-blog-card">
    <a class="sf-blog-card__media" href="{{ $url }}" aria-label="{{ $article['title'] ?? '' }}">
        <img src="{{ $image }}" alt="{{ $article['title'] ?? '' }}" loading="lazy"
             onerror="this.src='/assets/images/blog/blogThumb2_1.jpg'">
        @if (! empty($article['category']))
            <span class="sf-blog-card__badge">{{ $article['category'] }}</span>
        @endif
    </a>

    <div class="sf-blog-card__body">
        <div class="sf-blog-card__meta">
            @if ($date)
                <span><i class="fa-regular fa-calendar"></i> {{ $date->format('d.m.Y') }}</span>
            @endif
            @if (! empty($article['author_name']))
                <span><i class="fa-regular fa-user"></i> {{ $article['author_name'] }}</span>
            @endif
        </div>

        <h3 class="sf-blog-card__title"><a href="{{ $url }}">{{ $article['title'] ?? '' }}</a></h3>

        @if (! empty($article['description']))
            <p class="sf-blog-card__excerpt">{{ \Illuminate\Support\Str::limit($article['description'], 120) }}</p>
        @endif

        <a href="{{ $url }}" class="sf-blog-card__more">
            Oxu <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</article>
