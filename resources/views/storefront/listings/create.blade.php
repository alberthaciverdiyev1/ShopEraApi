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
@endphp

<x-storefront.layouts.app :title="'Elan yerləşdir — ShopEra'">
    <div class="elan-page">
        <div class="container elan-page__inner">
            <header class="elan-hero">
                <h1>Elan yerləşdir</h1>
                <p>Bir neçə addımda elanınızı dərc edin — qeydiyyat tələb olunmur.</p>
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
                    <span class="crumb current">Bütün kateqoriyalar</span>
                </nav>

                <p class="elan-hint">Bir kateqoriya seçin</p>

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

                <p class="elan-hint">Alt kateqoriya seçin</p>

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

                <form action="{{ route('storefront.listings.store') }}" method="POST" enctype="multipart/form-data" class="elan-form">
                    @csrf
                    <input type="hidden" name="category_id" value="{{ $selected['id'] }}">

                    @if (count($filters))
                        <section class="elan-section">
                            <h6 class="elan-section__title">Filtrlər</h6>
                            <div class="elan-grid">
                                @foreach ($filters as $filter)
                                    <label class="elan-field">
                                        <span>{{ $localized($filter['title']) }}{{ ! empty($filter['required']) ? ' *' : '' }}</span>
                                        <select name="filter_values[]"
                                                data-sf-filter="{{ $filter['id'] }}"
                                                data-sf-filter-depends="{{ $filter['depends_on_filter_id'] ?? '' }}"
                                                {{ ! empty($filter['depends_on_filter_id']) ? 'disabled' : '' }}>
                                            <option value="">Seçin…</option>
                                            @foreach ($filter['values'] as $value)
                                                <option value="{{ $value['id'] }}"
                                                        data-sf-parent-value="{{ $value['parent_value_id'] ?? '' }}"
                                                        @selected(in_array($value['id'], old('filter_values', []), true))>
                                                    {{ $localized($value['title']) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section class="elan-section">
                        <h6 class="elan-section__title">Əsas məlumat</h6>
                        <div class="elan-grid">
                            @if ($visible('title'))
                                <label class="elan-field elan-field--full">
                                    <span>Başlıq {{ $required('title') ? '*' : '' }}</span>
                                    <input type="text" name="title" value="{{ old('title') }}" maxlength="255"
                                           {{ $required('title') ? 'required' : '' }}
                                           placeholder="Məsələn: iPhone 15 Pro 256GB">
                                </label>
                            @endif

                            @if ($visible('description'))
                                <label class="elan-field elan-field--full">
                                    <span>Təsvir {{ $required('description') ? '*' : '' }}</span>
                                    <textarea name="description" rows="5" {{ $required('description') ? 'required' : '' }}
                                              placeholder="Vəziyyəti, xüsusiyyətləri və s. yazın…">{{ old('description') }}</textarea>
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

                            @if ($visible('price'))
                                <label class="elan-field">
                                    <span>Qiymət (₼) {{ $required('price') ? '*' : '' }}</span>
                                    <input type="number" name="price" min="0" step="0.01" value="{{ old('price') }}"
                                           {{ $required('price') ? 'required' : '' }}>
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
                            <div class="elan-thumbs" data-sf-dropzone-preview></div>
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
