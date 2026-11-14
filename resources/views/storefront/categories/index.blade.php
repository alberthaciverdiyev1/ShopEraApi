<x-storefront.layouts.app
    :title="'Bütün Kateqoriyalar — ShopEra'"
    :description="'ShopEra kateqoriyaları: bütün elan bölmələri və alt kateqoriyalar.'"
    :categories="$categories">

    <x-storefront.breadcrumb title="Kateqoriyalar" />

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
        $selectedCat = $selectedCategory ?? null;
        $defaultRootId = ! empty($selectedCat)
            ? (collect($roots)->first(fn ($c) => ($c['slug'] ?? '') == $selectedCat || ($c['id'] ?? '') == $selectedCat)['id'] ?? $roots->first()['id'] ?? null)
            : ($roots->first()['id'] ?? null);
    @endphp

    <div class="container sf-categories-page">
        <!-- Desktop: Exact Mega-Menu Layout from Navbar, with Photos for All Levels -->
        <div class="catalog-page-panel catalog-mega-panel columns-3 d-none d-lg-grid" data-sf-page-catalog>
            <!-- Column 1: Root Categories -->
            <div class="catalog-column catalog-column-primary" aria-label="Əsas kateqoriyalar">
                @foreach ($roots as $root)
                    @php
                        $hasChildren = count($childrenOf($root)) > 0;
                        $isDefault = $root['id'] == $defaultRootId;
                    @endphp
                    <a class="catalog-row @if($isDefault) active @endif"
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
                        @if ($hasChildren)
                            <i class="fa-solid fa-chevron-right catalog-chevron"></i>
                        @endif
                    </a>
                @endforeach
            </div>

            <!-- Column 2: Subcategories -->
            @foreach ($roots as $root)
                @php
                    $children = $childrenOf($root);
                    $isDefault = $root['id'] == $defaultRootId;
                @endphp
                @if (count($children) > 0)
                    <div class="catalog-column" data-catalog-children="{{ $root['id'] }}" @if(!$isDefault) hidden @endif aria-label="Alt kateqoriya">
                        <a class="catalog-row catalog-view-all" href="/shop?category={{ $root['slug'] ?? $root['id'] }}">
                            <span class="catalog-thumb catalog-thumb--sm">
                                @if (! empty($root['image']))
                                    <img src="{{ $root['image'] }}" alt="" loading="lazy">
                                @else
                                    <i class="fa-regular fa-grid-2"></i>
                                @endif
                            </span>
                            <span>Bütün "{{ $root['name'] }}" elanları</span>
                            <i class="fa-solid fa-arrow-right catalog-chevron"></i>
                        </a>

                        @foreach ($children as $child)
                            @php $hasLeaves = count($childrenOf($child)) > 0; @endphp
                            <a class="catalog-row @if($loop->first && $isDefault) active @endif"
                               href="/shop?category={{ $child['slug'] ?? $child['id'] }}"
                               data-catalog-child="{{ $child['id'] }}">
                                <span class="catalog-thumb">
                                    @if (! empty($child['image']))
                                        <img src="{{ $child['image'] }}" alt="" loading="lazy" onerror="this.remove()">
                                    @else
                                        <i class="fa-regular fa-folder"></i>
                                    @endif
                                </span>
                                <span>{{ $child['name'] }}</span>
                                @if ($hasLeaves)
                                    <i class="fa-solid fa-chevron-right catalog-chevron"></i>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif

                <!-- Column 3: Leaves -->
                @foreach ($children as $child)
                    @php
                        $leaves = $childrenOf($child);
                        $isInitial = ($isDefault && $loop->parent->first && $loop->first);
                    @endphp
                    @if (count($leaves) > 0)
                        <div class="catalog-column catalog-column-leaf" data-catalog-leaves="{{ $child['id'] }}" @if(!$isInitial) hidden @endif aria-label="Bölmələr">
                            <a class="catalog-row catalog-view-all" href="/shop?category={{ $child['slug'] ?? $child['id'] }}">
                                <span class="catalog-thumb catalog-thumb--sm">
                                    @if (! empty($child['image']))
                                        <img src="{{ $child['image'] }}" alt="" loading="lazy">
                                    @else
                                        <i class="fa-regular fa-folder"></i>
                                    @endif
                                </span>
                                <span>Bütün "{{ $child['name'] }}" elanları</span>
                                <i class="fa-solid fa-arrow-right catalog-chevron"></i>
                            </a>

                            @foreach ($leaves as $leaf)
                                <a class="catalog-row" href="/shop?category={{ $leaf['slug'] ?? $leaf['id'] }}">
                                    <span class="catalog-thumb">
                                        @if (! empty($leaf['image']))
                                            <img src="{{ $leaf['image'] }}" alt="" loading="lazy" onerror="this.remove()">
                                        @else
                                            <i class="fa-regular fa-image"></i>
                                        @endif
                                    </span>
                                    <span>{{ $leaf['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            @endforeach
        </div>

        <!-- Mobile: collapsible category list -->
        <div class="sf-mcat d-lg-none" data-sf-mcat>
            <header class="sf-mcat__head">
                <h1>Kateqoriyalar</h1>
                <p>{{ $roots->count() }} əsas bölmə</p>
            </header>

            <div class="sf-mcat__list">
                @foreach ($roots as $root)
                    @php $children = $childrenOf($root); @endphp
                    <section class="sf-mcat__item">
                        <button type="button" class="sf-mcat__trigger" data-sf-mcat-toggle
                                aria-expanded="false" aria-controls="sf-mcat-{{ $root['id'] }}">
                            <span class="sf-mcat__thumb">
                                @if (! empty($root['image']))
                                    <img src="{{ $root['image'] }}" alt="" loading="lazy" onerror="this.remove()">
                                @else
                                    <i class="fa-regular fa-grid-2"></i>
                                @endif
                            </span>
                            <span class="sf-mcat__label">
                                <span class="sf-mcat__name">{{ $root['name'] }}</span>
                                <span class="sf-mcat__meta">
                                    @if (count($children) > 0)
                                        {{ count($children) }} alt bölmə
                                    @else
                                        Bütün elanlar
                                    @endif
                                    @if (! empty($root['products_count']))
                                        · {{ (int) $root['products_count'] }} elan
                                    @endif
                                </span>
                            </span>
                            <i class="fa-solid fa-chevron-down sf-mcat__chev" aria-hidden="true"></i>
                        </button>

                        <div class="sf-mcat__body" id="sf-mcat-{{ $root['id'] }}" hidden>
                            <a class="sf-mcat__all" href="/shop?category={{ $root['slug'] ?? $root['id'] }}">
                                <span>Bütün “{{ $root['name'] }}” elanları</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>

                            @forelse ($children as $child)
                                @php $leaves = $childrenOf($child); @endphp
                                <div class="sf-mcat__child">
                                    <a class="sf-mcat__child-link" href="/shop?category={{ $child['slug'] ?? $child['id'] }}">
                                        <span class="sf-mcat__child-icon">
                                            @if (! empty($child['image']))
                                                <img src="{{ $child['image'] }}" alt="" loading="lazy" onerror="this.remove()">
                                            @else
                                                <i class="fa-regular fa-folder"></i>
                                            @endif
                                        </span>
                                        <span class="sf-mcat__child-name">{{ $child['name'] }}</span>
                                        <i class="fa-solid fa-chevron-right sf-mcat__child-chev" aria-hidden="true"></i>
                                    </a>

                                    @if (count($leaves) > 0)
                                        <div class="sf-mcat__leaves">
                                            @foreach ($leaves as $leaf)
                                                <a class="sf-mcat__chip" href="/shop?category={{ $leaf['slug'] ?? $leaf['id'] }}">{{ $leaf['name'] }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="sf-mcat__empty">Bu bölmənin alt kateqoriyası yoxdur.</p>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</x-storefront.layouts.app>

