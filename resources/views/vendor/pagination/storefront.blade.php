@if ($paginator->hasPages())
    <nav class="pagination-shell" role="navigation" aria-label="Səhifələmə">
        <div class="pagination-summary">
            {{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }}
            arası göstərilir, cəmi {{ $paginator->total() }} nəticə
        </div>

        <div class="pagination-links">
            @if ($paginator->onFirstPage())
                <span class="is-disabled" aria-disabled="true" aria-label="Əvvəlki səhifə">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Əvvəlki səhifə">‹</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="is-disabled" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Növbəti səhifə">›</a>
            @else
                <span class="is-disabled" aria-disabled="true" aria-label="Növbəti səhifə">›</span>
            @endif
        </div>
    </nav>
@endif
