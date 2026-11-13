@props(['categories' => []])

@php
    $childrenOf = function ($category) {
        $children = $category['children'] ?? [];

        if ($children instanceof \Illuminate\Http\Resources\Json\ResourceCollection) {
            $children = $children->resolve();
        } elseif ($children instanceof \Illuminate\Support\Collection) {
            $children = $children->all();
        }

        return is_array($children) ? $children : [];
    };

    $roots = collect($categories)->values();
@endphp

<div class="sub-cataegory catalog-mega-panel columns-1" data-sf-catalog-menu>
    <div class="catalog-column catalog-column-primary" aria-label="Kateqoriyalar">
        @foreach ($roots as $root)
            <a class="catalog-row @if ($loop->first) active @endif"
               href="/shop?category={{ $root['slug'] ?? $root['id'] }}"
               data-catalog-parent="{{ $root['id'] }}">
                <span class="catalog-thumb">
                    @if (! empty($root['image']))
                        <img src="{{ $root['image'] }}" alt="" loading="lazy" onerror="this.remove()">
                    @else
                        <i class="fa-regular fa-grid-2"></i>
                    @endif
                </span>
                <span>{{ $root['name'] }}</span>
                @if (count($childrenOf($root)) > 0)
                    <i class="fa-solid fa-chevron-right catalog-chevron"></i>
                @endif
            </a>
        @endforeach
    </div>

    @foreach ($roots as $root)
        @php $children = $childrenOf($root); @endphp

        @if (count($children) > 0)
            <div class="catalog-column" data-catalog-children="{{ $root['id'] }}" hidden aria-label="Alt kateqoriya">
                <a class="catalog-row catalog-row-text catalog-view-all" href="/shop?category={{ $root['slug'] ?? $root['id'] }}">
                    <span>{{ $root['name'] }}</span>
                    <i class="fa-solid fa-chevron-right catalog-chevron"></i>
                </a>

                @foreach ($children as $child)
                    <a class="catalog-row catalog-row-text"
                       href="/shop?category={{ $child['slug'] ?? $child['id'] }}"
                       data-catalog-child="{{ $child['id'] }}">
                        <span>{{ $child['name'] }}</span>
                        @if (count($childrenOf($child)) > 0)
                            <i class="fa-solid fa-chevron-right catalog-chevron"></i>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        @foreach ($children as $child)
            @php $leaves = $childrenOf($child); @endphp

            @if (count($leaves) > 0)
                <div class="catalog-column catalog-column-leaf" data-catalog-leaves="{{ $child['id'] }}" hidden aria-label="Alt kateqoriya">
                    <a class="catalog-leaf-link catalog-leaf-link-featured" href="/shop?category={{ $child['slug'] ?? $child['id'] }}">
                        {{ $child['name'] }}
                    </a>

                    @foreach ($leaves as $leaf)
                        <a class="catalog-leaf-link" href="/shop?category={{ $leaf['slug'] ?? $leaf['id'] }}">
                            {{ $leaf['name'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    @endforeach
</div>
