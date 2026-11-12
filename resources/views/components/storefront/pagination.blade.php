@props(['meta' => []])

@php
    $current = (int) ($meta['current_page'] ?? 1);
    $last = (int) ($meta['last_page'] ?? 1);
    $query = request()->query();

    $url = fn (int $page) => url('/shop').'?'.http_build_query(array_merge($query, ['page' => $page]));

    $start = max(1, $current - 2);
    $end = min($last, $current + 2);
@endphp

@if ($last > 1)
    <nav class="sf-pagination" aria-label="Səhifələnmə">
        @if ($current > 1)
            <a class="sf-pagination__link" href="{{ $url($current - 1) }}" rel="prev">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
        @endif

        @if ($start > 1)
            <a class="sf-pagination__link" href="{{ $url(1) }}">1</a>
            @if ($start > 2)
                <span class="sf-pagination__dots">…</span>
            @endif
        @endif

        @for ($page = $start; $page <= $end; $page++)
            @if ($page === $current)
                <span class="sf-pagination__link is-active" aria-current="page">{{ $page }}</span>
            @else
                <a class="sf-pagination__link" href="{{ $url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($end < $last)
            @if ($end < $last - 1)
                <span class="sf-pagination__dots">…</span>
            @endif
            <a class="sf-pagination__link" href="{{ $url($last) }}">{{ $last }}</a>
        @endif

        @if ($current < $last)
            <a class="sf-pagination__link" href="{{ $url($current + 1) }}" rel="next">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        @endif
    </nav>
@endif
