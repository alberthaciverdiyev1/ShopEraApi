@php
    $localized = function ($value): string {
        if (is_array($value)) {
            return $value['az'] ?? $value['en'] ?? (string) reset($value);
        }

        return (string) ($value ?? '');
    };

    $visible = fn (string $field) => ($schema[$field]['visible'] ?? true) !== false;
    $required = fn (string $field) => ($schema[$field]['required'] ?? false) === true;
    $isGuest = auth()->guest();

    // Variant pickers and the per-photo selects read these plain arrays.
    $colorChoices = collect($colors ?? [])->map(fn ($color) => [
        'id' => (int) ($color['id'] ?? 0),
        'name' => $localized($color['name'] ?? ''),
        'hex' => $color['hex'] ?? '#94a3b8',
    ])->filter(fn ($color) => $color['id'] > 0)->values()->all();

    $sizeChoices = collect($sizes ?? [])->map(fn ($size) => [
        'id' => (int) ($size['id'] ?? 0),
        'name' => $localized($size['name'] ?? ''),
    ])->filter(fn ($size) => $size['id'] > 0)->values()->all();

    $oldInts = fn (string $key): array => array_map('intval', (array) old($key, []));
    $oldColors = $oldInts('colors');
    $oldSizes = $oldInts('sizes');
@endphp

<x-storefront.layouts.app :title="'Elan yerləşdir — ShopEra'">
    <div class="elan-page">
        <div class="container elan-page__inner">
            <header class="elan-hero">
                <h1>Elan yerləşdir</h1>
            </header>

            <div class="elan-steps">
                <div class="elan-step {{ $selected ? 'done' : 'active' }}">
                    <span class="dot">{{ $selected ? '✓' : '1' }}</span><span>Kateqoriya</span>
                </div>
                <div class="elan-bar {{ $selected ? 'filled' : '' }}"></div>
                <div class="elan-step {{ $selected ? 'active' : '' }}">
                    <span class="dot">2</span><span>Elan məlumatları</span>
                </div>
            </div>

            @if ($published)
                <div class="elan-success">
                    <div class="elan-success__icon"><i class="fa-solid fa-circle-check"></i></div>
                    <h3>Elan uğurla yerləşdirildi!</h3>
                    <p>Bu elanı sonradan <strong>redaktə və ya silmək</strong> üçün aşağıdakı linki saxlayın — giriş tələb olunmur.</p>
                    <div class="elan-copy-row">
                        <input class="form-control" readonly value="{{ $published['manage_url'] }}">
                        <a class="theme-btn" href="{{ $published['manage_url'] }}">Aç</a>
                    </div>
                    <p class="elan-warn"><i class="fa-solid fa-triangle-exclamation"></i> Bu linki itirsəniz, elanı idarə edə bilməyəcəksiniz.</p>
                </div>
            @elseif (! $selected)
                <nav class="elan-crumbs" aria-label="Kateqoriya yolu">
                    <span class="crumb current">Bir kateqoriya seçin</span>
                </nav>

                <div class="elan-cat-grid">
                    @foreach ($children as $category)
                        <a class="elan-cat-card" href="/elan/ver?category={{ $category['slug'] ?? $category['id'] }}">
                            @if (! empty($category['image']))
                                <span class="cat-thumb" style="background-image:url('{{ $category['image'] }}')"></span>
                            @else
                                <span class="cat-thumb placeholder">{{ mb_strtoupper(mb_substr($localized($category['name']), 0, 1)) }}</span>
                            @endif
                            <span class="cat-name">{{ $localized($category['name']) }}</span>
                            @if (count($category['children'] ?? []))
                                <span class="cat-count">{{ count($category['children']) }} alt kateqoriya <i class="fas fa-chevron-right"></i></span>
                            @else
                                <span class="cat-count">seç <i class="fas fa-check"></i></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @elseif (! $isLeaf)
                <nav class="elan-crumbs" aria-label="Kateqoriya yolu">
                    <a class="crumb" href="/elan/ver">Bütün kateqoriyalar</a>
                    @foreach ($path as $crumb)
                        <span class="sep">/</span>
                        <a class="crumb {{ $loop->last ? 'current' : '' }}" href="/elan/ver?category={{ $crumb['slug'] ?? $crumb['id'] }}">
                            {{ $localized($crumb['name']) }}
                        </a>
                    @endforeach
                </nav>

                <div class="elan-cat-grid">
                    @foreach ($children as $category)
                        <a class="elan-cat-card" href="/elan/ver?category={{ $category['slug'] ?? $category['id'] }}">
                            @if (! empty($category['image']))
                                <span class="cat-thumb" style="background-image:url('{{ $category['image'] }}')"></span>
                            @else
                                <span class="cat-thumb placeholder">{{ mb_strtoupper(mb_substr($localized($category['name']), 0, 1)) }}</span>
                            @endif
                            <span class="cat-name">{{ $localized($category['name']) }}</span>
                            @if (count($category['children'] ?? []))
                                <span class="cat-count">{{ count($category['children']) }} alt kateqoriya <i class="fas fa-chevron-right"></i></span>
                            @else
                                <span class="cat-count">seç <i class="fas fa-check"></i></span>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="elan-extra">
                    <a class="sf-btn sf-btn--ghost" href="{{ count($path) > 1 ? '/elan/ver?category='.($path[count($path) - 2]['slug'] ?? $path[count($path) - 2]['id']) : '/elan/ver' }}">
                        <i class="fas fa-arrow-left"></i> Geri
                    </a>
                </div>
            @else
                <div class="elan-picked-bar">
                    <div>
                        <span class="muted">Kateqoriya</span>
                        <strong>{{ $localized($selected['name']) }}</strong>
                    </div>
                    <a href="/elan/ver" class="sf-btn sf-btn--ghost sf-btn--sm">Dəyiş</a>
                </div>

                @if ($errors->any())
                    <div class="sf-alert sf-alert--error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('storefront.listings.store') }}" method="POST" enctype="multipart/form-data"
                      class="elan-form"
                      data-sf-listing-form
                      data-sf-category="{{ $localized($selected['name']) }}">
                    @csrf
                    <input type="hidden" name="category_id" value="{{ $selected['id'] }}">

                    @php
                        $filterOptions = fn (array $filter): array => array_values(array_filter(
                            array_map('strval', (array) ($filter['options'] ?? [])),
                            fn (string $option) => $option !== ''
                        ));
                        $oldFilterValues = fn (int $filterId): array => array_map(
                            'strval',
                            array_filter((array) old('filters.'.$filterId, []), fn ($value) => $value !== null && $value !== '')
                        );
                    @endphp

                    @if (count($filters))
                        <section class="elan-section">
                            <h6 class="elan-section__title">Filtrlər</h6>
                            <div class="elan-grid">
                                @foreach ($filters as $filter)
                                    @php
                                        $filterId = (int) $filter['id'];
                                        $options = $filterOptions($filter);
                                        $selectedValues = $oldFilterValues($filterId);
                                        $isMulti = ($filter['type'] ?? 'select') === 'checkbox';
                                    @endphp
                                    @if ($options !== [])
                                        @if ($isMulti)
                                            <div class="elan-field elan-field--full">
                                                <span>{{ $localized($filter['title']) }}{{ ! empty($filter['required']) ? ' *' : '' }}</span>
                                                <div class="elan-checks">
                                                    @foreach ($options as $option)
                                                        <label class="elan-check-pill">
                                                            <input type="checkbox"
                                                                   name="filters[{{ $filterId }}][]"
                                                                   value="{{ $option }}"
                                                                   @checked(in_array($option, $selectedValues, true))>
                                                            <span>{{ $option }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <label class="elan-field">
                                                <span>{{ $localized($filter['title']) }}{{ ! empty($filter['required']) ? ' *' : '' }}</span>
                                                <select name="filters[{{ $filterId }}]" {{ ! empty($filter['required']) ? 'required' : '' }}>
                                                    <option value="">Seçin…</option>
                                                    @foreach ($options as $option)
                                                        <option value="{{ $option }}" @selected(in_array($option, $selectedValues, true))>{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section class="elan-section">
                        <h6 class="elan-section__title">Əsas məlumat</h6>
                        <div class="elan-grid">
                            @if ($visible('title'))
                                @php
                                    $autoTitleChecked = old('_token') === null || old('auto_title') === '1';
                                @endphp
                                <div class="elan-field elan-field--full">
                                    <span>Başlıq {{ $required('title') ? '*' : '' }}</span>
                                    <input type="text" name="title" value="{{ old('title') }}" maxlength="255"
                                           data-sf-title-input
                                           data-required="{{ $required('title') ? '1' : '0' }}"
                                           placeholder="Məsələn: Apple iPhone 15 Pro 256GB">
                                    <label class="elan-check-pill elan-auto-title">
                                        <input type="checkbox" name="auto_title" value="1"
                                               data-sf-auto-title-toggle
                                               @checked($autoTitleChecked)>
                                        <span>Başlıq avtomatik yaradılsın</span>
                                    </label>
                                    <span class="elan-hint">Marka, model, kateqoriya və seçilmiş filtrlərdən Trendyol üslubunda avtomatik başlıq yaradılır. Söndürsəniz, başlığı özünüz yaza bilərsiniz.</span>
                                </div>
                            @endif

                            @if ($visible('description'))
                                <label class="elan-field elan-field--full">
                                    <span>Təsvir {{ $required('description') ? '*' : '' }}</span>
                                    <textarea name="description" rows="5" {{ $required('description') ? 'required' : '' }}
                                              placeholder="Vəziyyəti, xüsusiyyətləri və s. yazın…">{{ old('description') }}</textarea>
                                </label>
                            @endif

                            @if ($visible('price'))
                                <label class="elan-field">
                                    <span>Qiymət (₼) {{ $required('price') ? '*' : '' }}</span>
                                    <input type="number" name="price" min="0" step="0.01" value="{{ old('price') }}"
                                           {{ $required('price') ? 'required' : '' }}>
                                </label>
                                <label class="elan-field">
                                    <span>Endirimli qiymət (₼)</span>
                                    <input type="number" name="discount" min="0" step="0.01" value="{{ old('discount') }}"
                                           placeholder="Endirim yoxdursa boş buraxın">
                                </label>
                            @endif

                            @if ($visible('city'))
                                <label class="elan-field">
                                    <span>Şəhər {{ $required('city') ? '*' : '' }}</span>
                                    <select name="city_key" {{ $required('city') ? 'required' : '' }}>
                                        <option value="">Seçin…</option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city['key'] }}" @selected(old('city_key') === $city['key'])>{{ $city['name'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif

                            @if ($visible('condition') || $visible('delivery'))
                                <div class="elan-field elan-field--full elan-checks">
                                    @if ($visible('condition'))
                                        <label class="elan-check-pill">
                                            <input type="checkbox" name="condition" value="new" @checked(old('condition') === 'new')>
                                            <span>Yeni</span>
                                        </label>
                                    @endif
                                    @if ($visible('delivery'))
                                        <label class="elan-check-pill">
                                            <input type="checkbox" name="has_delivery" value="1" @checked(old('has_delivery'))>
                                            <span>Çatdırılma var</span>
                                        </label>
                                    @endif
                                </div>
                            @endif

                            @if (! empty($selected['needs_brand']))
                                <label class="elan-field">
                                    <span>Marka *</span>
                                    <select name="brand_id" required>
                                        <option value="">Seçin…</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand['id'] }}" @selected((string) old('brand_id') === (string) $brand['id'])>{{ $brand['name'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="elan-field">
                                    <span>Model</span>
                                    <input type="text" name="model" value="{{ old('model') }}" placeholder="Məsələn: iPhone 15 Pro">
                                </label>
                            @endif
                        </div>
                    </section>

                    @if (count($colorChoices) || count($sizeChoices))
                        <section class="elan-section">
                            <h6 class="elan-section__title">Rəng və ölçü variantları</h6>
                            <div class="elan-grid">
                                @if (count($colorChoices))
                                    <div class="elan-field elan-field--full">
                                        <span>Rənglər <span class="muted small">(bir neçəsini seçə bilərsiniz)</span></span>
                                        <div class="elan-checks">
                                            @foreach ($colorChoices as $color)
                                                <label class="elan-check-pill elan-color-pill">
                                                    <input type="checkbox" name="colors[]" value="{{ $color['id'] }}" data-sf-color-check
                                                           @checked(in_array($color['id'], $oldColors, true))>
                                                    <span class="elan-color-dot" style="background-color: {{ $color['hex'] }}"></span>
                                                    <span>{{ $color['name'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if (count($sizeChoices))
                                    <div class="elan-field elan-field--full">
                                        <span>Ölçülər <span class="muted small">(hər ölçü üçün ayrı stok və qiymət yaza bilərsiniz)</span></span>
                                        <label class="elan-check-pill elan-standard-price">
                                            <input type="hidden" name="standard_price" value="0">
                                            <input type="checkbox" name="standard_price" value="1" data-sf-standard-price
                                                   @checked(old('standard_price', '1') === '1')>
                                            <span>Bütün rəng və ölçülər üçün standart qiymət</span>
                                        </label>
                                        <div class="elan-size-list">
                                            @foreach ($sizeChoices as $size)
                                                <div class="elan-size-row" data-sf-size-row>
                                                    <label class="elan-check-pill">
                                                        <input type="checkbox" name="sizes[]" value="{{ $size['id'] }}" data-sf-size-check
                                                               @checked(in_array($size['id'], $oldSizes, true))>
                                                        <span>{{ $size['name'] }}</span>
                                                    </label>
                                                    <div class="elan-size-prices">
                                                        <input type="number" name="size_stocks[{{ $size['id'] }}]" min="0" step="1"
                                                               placeholder="Stok" data-sf-size-stock
                                                               value="{{ old('size_stocks.'.$size['id']) }}">
                                                        <input type="number" name="size_prices[{{ $size['id'] }}]" min="0" step="0.01"
                                                               placeholder="Qiymət (₼)" data-sf-size-price
                                                               value="{{ old('size_prices.'.$size['id']) }}">
                                                        <input type="number" name="size_discounts[{{ $size['id'] }}]" min="0" step="0.01"
                                                               placeholder="Endirimli (₼)" data-sf-size-price
                                                               value="{{ old('size_discounts.'.$size['id']) }}">
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <span class="elan-hint">Aktiv olduqda əsas qiymət bütün rəng və ölçülərə tətbiq olunur. Söndürsəniz, yalnız seçdiyiniz ölçülər üçün ayrı qiymət və endirim yaza bilərsiniz; stok həmişə ölçü üzrə verilir.</span>
                                    </div>
                                @endif
                            </div>
                        </section>
                    @endif

                    @if ($visible('photos'))
                        <section class="elan-section">
                            <h6 class="elan-section__title">Şəkillər {{ $required('photos') ? '*' : '' }} <span class="muted">(maks. 10)</span></h6>
                            <label class="elan-dropzone" data-sf-dropzone>
                                <input type="file" name="images[]" accept="image/*" multiple class="d-none" data-sf-dropzone-input
                                       {{ $required('photos') ? 'required' : '' }}>
                                <div class="dz-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                <div><strong>Şəkilləri bura sürükləyin</strong> və ya <span class="link">fayl seçin</span></div>
                                <div class="muted small">JPG, PNG, WEBP — hər biri maks. 8 MB</div>
                            </label>
                            <div class="elan-thumbs"
                                 data-sf-dropzone-preview
                                 data-sf-colors="{{ json_encode($colorChoices) }}"
                                 data-sf-sizes="{{ json_encode($sizeChoices) }}"></div>
                            <p class="elan-hint">Hər şəkil üçün hansı rəng və ölçünü göstərdiyini seçin — məhsul səhifəsində rəng/ölçü dəyişdikdə uyğun şəkillər göstəriləcək.</p>
                        </section>
                    @endif

                    <section class="elan-section">
                        <h6 class="elan-section__title">Əlaqə</h6>
                        @if ($isGuest)
                            <div class="elan-grid">
                                <label class="elan-field">
                                    <span>Adınız *</span>
                                    <input type="text" name="contact_name" value="{{ old('contact_name') }}" required>
                                </label>
                                <label class="elan-field">
                                    <span>Telefon *</span>
                                    <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" required placeholder="+994...">
                                </label>
                                <label class="elan-field">
                                    <span>E-poçt</span>
                                    <input type="email" name="contact_email" value="{{ old('contact_email') }}">
                                </label>
                            </div>
                        @else
                            <p class="text-muted mb-0">Əlaqə məlumatları hesabınızdan götürüləcək ({{ auth()->user()->name }}).</p>
                        @endif
                    </section>

                    @if (! $isGuest && ! empty($publishAsStore))
                        <section class="elan-section">
                            <h6 class="elan-section__title">Paylaşım</h6>
                            <div class="elan-grid">
                                <label class="elan-field">
                                    <span>Elan kimin adından paylaşılır?</span>
                                    <select name="publish_as" required>
                                        <option value="store" @selected(old('publish_as', 'store') === 'store')>
                                            Mağaza adından — {{ $publishAsStore }}
                                        </option>
                                        <option value="personal" @selected(old('publish_as') === 'personal')>
                                            Şəxsi hesabdan — {{ auth()->user()->name }}
                                        </option>
                                    </select>
                                </label>
                            </div>
                            <p class="muted small mb-0">Mağaza adından paylaşılan elanlar mağaza səhifəsində görünür.</p>
                        </section>
                    @endif

                    <div class="elan-actions">
                        <button type="submit" class="theme-btn elan-submit">Elanı yerləşdir</button>
                        <a class="sf-btn sf-btn--ghost" href="/elan/ver">Geri</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-storefront.layouts.app>
