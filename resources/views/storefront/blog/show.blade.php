@php
    $title = $article['title'] ?? 'Bloq';
    $image = $article['image'] ?? '/assets/images/blog/blogThumb2_1.jpg';
    $date = ! empty($article['published_at']) ? \Illuminate\Support\Carbon::parse($article['published_at']) : null;
    $related = $article['related_posts'] ?? [];
    $description = \Illuminate\Support\Str::limit(strip_tags((string) ($article['description'] ?? $article['content'] ?? '')), 155);
@endphp

<x-storefront.layouts.app
    :title="$title.' — ShopEra'"
    :description="$description"
    :categories="$categories">

    <x-storefront.breadcrumb :title="$title" parent="Bloq" />

    <div class="container sf-blog-show">
        <div class="sf-blog-show__grid">
            <article class="sf-blog-article">
                <div class="sf-blog-article__meta">
                    @if (! empty($article['category']))
                        <span class="sf-blog-card__badge">{{ $article['category'] }}</span>
                    @endif
                    @if ($date)
                        <span><i class="fa-regular fa-calendar"></i> {{ $date->format('d.m.Y') }}</span>
                    @endif
                    @if (! empty($article['author_name']))
                        <span><i class="fa-regular fa-user"></i> {{ $article['author_name'] }}</span>
                    @endif
                    @if (isset($article['views']))
                        <span><i class="fa-regular fa-eye"></i> {{ (int) $article['views'] }} baxış</span>
                    @endif
                </div>

                <h1 class="sf-blog-article__title">{{ $title }}</h1>

                <div class="sf-blog-article__banner">
                    <img src="{{ $image }}" alt="{{ $title }}" onerror="this.src='/assets/images/blog/blogThumb2_1.jpg'">
                </div>

                @if (! empty($article['description']))
                    <p class="sf-blog-article__lead">{{ $article['description'] }}</p>
                @endif

                @if (! empty($article['content']))
                    <div class="sf-prose">{!! $article['content'] !!}</div>
                @endif

                @if (! empty($article['tags']))
                    <div class="sf-blog-article__tags">
                        <strong>Teqlər:</strong>
                        @foreach ($article['tags'] as $tag)
                            <a href="/blog?search={{ urlencode($tag) }}" class="sf-tag-pill">#{{ $tag }}</a>
                        @endforeach
                    </div>
                @endif
            </article>

            <aside class="sf-blog-sidebar">
                @if (count($blogCategories) > 0)
                    <div class="sf-sidebar-card">
                        <h2>Kateqoriyalar</h2>
                        <ul class="sf-sidebar-list">
                            @foreach ($blogCategories as $category)
                                <li>
                                    <a href="/blog?category={{ urlencode($category['name']) }}">{{ $category['name'] }}</a>
                                    <span>{{ $category['count'] ?? 0 }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (count($related) > 0)
                    <div class="sf-sidebar-card">
                        <h2>Son məqalələr</h2>
                        <div class="sf-sidebar-recent">
                            @foreach ($related as $post)
                                @php
                                    $postDate = ! empty($post['published_at']) ? \Illuminate\Support\Carbon::parse($post['published_at']) : null;
                                @endphp
                                <a class="sf-sidebar-recent__item" href="/blog/{{ $post['slug'] ?? '' }}">
                                    <img src="{{ $post['image'] ?? '/assets/images/blog/blogThumb2_1.jpg' }}" alt="" loading="lazy"
                                         onerror="this.src='/assets/images/blog/blogThumb2_1.jpg'">
                                    <span>
                                        @if ($postDate) <small>{{ $postDate->format('d.m.Y') }}</small> @endif
                                        <strong>{{ $post['title'] ?? '' }}</strong>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
        </div>

        @if (count($related) > 0)
            <section class="sf-blog-related">
                <div class="sf-section-head">
                    <h2>Bənzər məqalələr</h2>
                    <a href="/blog">Hamısına bax <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="sf-blog-grid sf-blog-grid--related">
                    @foreach ($related as $post)
                        <x-storefront.blog-card :article="$post" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-storefront.layouts.app>
