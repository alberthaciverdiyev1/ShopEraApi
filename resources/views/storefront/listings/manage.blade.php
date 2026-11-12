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
                            <span>Vəziyyət</span>
                            <select name="condition">
                                <option value="used" @selected(old('condition', $isNew ? 'new' : 'used') === 'used')>İşlənmiş</option>
                                <option value="new" @selected(old('condition', $isNew ? 'new' : 'used') === 'new')>Yeni</option>
                            </select>
                        </label>
                    </div>

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
