@props([
    'categories' => [],
    'title' => 'Kateqoriyalar',
])

@php
    $all = collect($categories);

    // Admins pick the strip through `show_on_home`. On a catalogue where
    // nothing is flagged yet, fall back to root categories instead of
    // dumping every leaf into the strip.
    $shown = $all->contains(fn ($category) => ! empty($category['show_on_home']))
        ? $all->filter(fn ($category) => ! empty($category['show_on_home']))->values()
        : $all->filter(fn ($category) => empty($category['parent_id']))->values();

    $palette = [
        '#fde8e1', '#e0f2fe', '#e6f4ea', '#e0f2f1', '#f3e8ff',
        '#e0f2fe', '#fce7f3', '#f3e8ff', '#e8f5e9', '#fef3c7',
    ];
@endphp

@if ($shown->isNotEmpty())
    <section class="sf-category-strip" aria-label="{{ $title }}">
        <div class="container">
            <div class="sf-category-grid">
                @foreach ($shown as $index => $category)
                    <a
                        class="sf-category-card"
                        href="/shop?category={{ $category['id'] }}"
                        style="--card-bg: {{ ! empty($category['background_color']) ? $category['background_color'] : $palette[$index % count($palette)] }}"
                    >
                        <span class="sf-category-card__name">{{ $category['name'] }}</span>
                        <span class="sf-category-card__thumb">
                            @if (! empty($category['image']))
                                <img src="{{ $category['image'] }}" alt="{{ $category['name'] }}" loading="lazy">
                            @else
                                <i class="fa-regular fa-grid-2"></i>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
