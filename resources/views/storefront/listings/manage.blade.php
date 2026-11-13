@php
    $fallback = '/assets/images/top-deals-item/topDealsItemThumb1_1.png';
    $localized = function ($value): string {
        if (is_array($value)) {
            return $value['az'] ?? $value['en'] ?? (string) reset($value);
        }

        return (string) ($value ?? '');
    };
    $title = $localized($listing['title'] ?? '');
    $description = $localized($listing['description'] ?? '');
    $image = $listing['images'][0] ?? $fallback;
    $isNew = ($listing['condition'] ?? 'used') === 'new';

    // The create form's colour and size pickers, prefilled with what the
    // listing already has; sizes keep their per-size price and stock.
    $colorOptions = collect($colors ?? [])->map(fn ($color) => [
        'id' => (int) ($color['id'] ?? 0),
        'name' => $localized($color['name'] ?? ''),
        'hex' => $color['hex'] ?? '#94a3b8',
    ])->filter(fn ($color) => $color['id'] > 0)->values()->all();

    $sizeOptions = collect($sizes ?? [])->map(fn ($size) => [
        'id' => (int) ($size['id'] ?? 0),
        'name' => $localized($size['name'] ?? ''),
    ])->filter(fn ($size) => $size['id'] > 0)->values()->all();

    $currentSizes = collect($listing['sizes'] ?? [])->keyBy('id');

    $selectedColors = array_map('intval', (array) old(
        'colors',
        array_column($listing['colors'] ?? [], 'id')
    ));
    $selectedSizes = array_map('intval', (array) old(
        'sizes',
        $currentSizes->keys()->all()
    ));

    // No size carries its own price when the standard price is in effect.
    $hasOwnPrice = $currentSizes->contains(fn ($size) => $size['price'] !== null || $size['discount'] !== null);
    $standardOn = (string) old('standard_price', $hasOwnPrice ? '0' : '1') === '1';
@endphp

<x-storefront.layouts.app :title="'Elanı idarə et — ShopEra'">
    <div class="section-padding fix elan-page">
        <div class="container elan-page__narrow-sm">
            <h2 class="mb-1">Elanı idarə et</h2>
            <p class="text-muted mb-4">Bu link elanınızı idarə etmək üçün gizli açardır — kiminlə paylaşmayın.</p>

            @if (session('status'))
                <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="sf-alert sf-alert--error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <img src="{{ $image }}" alt="{{ $title }}" class="mb-4" style="max-height: 280px; width: 100%; object-fit: cover; border-radius: 16px;">

            <form action="{{ route('storefront.listings.manage.update', $token) }}" method="POST" enctype="multipart/form-data" class="sf-panel">
                @csrf
                @method('PUT')

                <div class="sf-account__form">
                    <label>
                        <span>Başlıq *</span>
                        <input type="text" name="title" value="{{ old('title', $title) }}" required maxlength="255">
                    </label>

                    <label>
                        <span>Təsvir *</span>
                        <textarea name="description" rows="4" required maxlength="5000">{{ old('description', $description) }}</textarea>
                    </label>

                    <div class="sf-form-row">
                        <label>
                            <span>Qiymət (₼) *</span>
                            <input type="number" name="price" value="{{ old('price', $listing['price'] ?? '') }}" min="0" step="0.01" required>
                        </label>
                        <label>
                            <span>Endirimli qiymət (₼)</span>
                            <input type="number" name="discount" value="{{ old('discount', $listing['discount'] ?? '') }}" min="0" step="0.01" placeholder="Endirim yoxdursa boş buraxın">
                        </label>
                        <label>
                            <span>Stok <span class="muted small">(ümumi)</span></span>
                            <input type="number" name="stock_count" value="{{ old('stock_count', $listing['stock_count'] ?? 1) }}" min="0" step="1">
                        </label>
                    </div>

                    <div class="sf-form-row">
                        <label>
                            <span>Vəziyyət</span>
                            <select name="condition">
                                <option value="used" @selected(old('condition', $isNew ? 'new' : 'used') === 'used')>İşlənmiş</option>
                                <option value="new" @selected(old('condition', $isNew ? 'new' : 'used') === 'new')>Yeni</option>
                            </select>
                        </label>
                    </div>

                    @if (count($colorOptions))
                        <div class="elan-field elan-field--full elan-variant-group">
                            <span>Rənglər <span class="muted small">(bir neçəsini seçə bilərsiniz)</span></span>
                            <div class="elan-checks">
                                @foreach ($colorOptions as $color)
                                    <label class="elan-check-pill elan-color-pill">
                                        <input type="checkbox" name="colors[]" value="{{ $color['id'] }}" data-sf-color-check
                                               @checked(in_array($color['id'], $selectedColors, true))>
                                        <span class="elan-color-dot" style="background-color: {{ $color['hex'] }}"></span>
                                        <span>{{ $color['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (count($sizeOptions))
                        <div class="elan-field elan-field--full elan-variant-group">
                            <span>Ölçülər <span class="muted small">(hər ölçü üçün ayrıca stok və qiymət)</span></span>

                            <label class="elan-check-pill elan-standard-price">
                                <input type="hidden" name="standard_price" value="0">
                                <input type="checkbox" name="standard_price" value="1" data-sf-standard-price @checked($standardOn)>
                                <span>Bütün rəng və ölçülər üçün standart qiymət</span>
                            </label>

                            <div class="elan-size-list">
                                @foreach ($sizeOptions as $size)
                                    @php($row = $currentSizes->get($size['id']))
                                    <div class="elan-size-row" data-sf-size-row>
                                        <label class="elan-check-pill">
                                            <input type="checkbox" name="sizes[]" value="{{ $size['id'] }}" data-sf-size-check
                                                   @checked(in_array($size['id'], $selectedSizes, true))>
                                            <span>{{ $size['name'] }}</span>
                                        </label>
                                        <div class="elan-size-prices">
                                            <input type="number" name="size_stocks[{{ $size['id'] }}]" min="0" step="1"
                                                   placeholder="Stok" data-sf-size-stock
                                                   value="{{ old('size_stocks.'.$size['id'], $row['stock'] ?? null) }}">
                                            <input type="number" name="size_prices[{{ $size['id'] }}]" min="0" step="0.01"
                                                   placeholder="Qiymət (₼)" data-sf-size-price
                                                   value="{{ old('size_prices.'.$size['id'], $row['price'] ?? null) }}">
                                            <input type="number" name="size_discounts[{{ $size['id'] }}]" min="0" step="0.01"
                                                   placeholder="Endirimli (₼)" data-sf-size-price
                                                   value="{{ old('size_discounts.'.$size['id'], $row['discount'] ?? null) }}">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <span class="elan-hint">Standart qiymət aktiv olduqda əsas qiymət bütün rəng və ölçülərə tətbiq olunur.</span>
                        </div>
                    @endif

                    <label class="sf-check-row">
                        <input type="hidden" name="has_delivery" value="0">
                        <input type="checkbox" name="has_delivery" value="1" @checked(old('has_delivery', $listing['has_delivery'] ?? false))>
                        <span>Çatdırılma var</span>
                    </label>

                    <label>
                        <span>Yeni şəkillər</span>
                        <input type="file" name="images[]" accept="image/*" multiple>
                    </label>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="theme-btn">Yadda saxla</button>
                        <button type="submit" form="sf-manage-delete" class="sf-btn sf-btn--ghost"
                                onclick="return confirm('Elan silinsin?');">Sil</button>
                    </div>
                </div>
            </form>

            <form id="sf-manage-delete" action="{{ route('storefront.listings.manage.destroy', $token) }}" method="POST">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</x-storefront.layouts.app>
