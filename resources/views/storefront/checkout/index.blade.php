@php
    $fallback = '/assets/images/top-deals-item/topDealsItemThumb1_1.png';
    $money = fn ($value) => number_format((float) $value, 2).' ₼';
    $addressTypeId = match ($deliveryType) {
        'PICKUP_POINT' => $pickupPointId,
        'TAKE_FROM_STORE' => null,
        default => $addressId,
    };
    $selectedAddress = null;
    foreach ($addresses as $address) {
        if ((int) $address['id'] === (int) $addressId) {
            $selectedAddress = $address;
            break;
        }
    }
    $selectedPickup = null;
    foreach ($pickupPoints as $point) {
        if ((int) $point['id'] === (int) $pickupPointId) {
            $selectedPickup = $point;
            break;
        }
    }
    $deliveryLabel = match ($deliveryType) {
        'PICKUP_POINT' => 'Təhvil məntəqəsi',
        'TAKE_FROM_STORE' => 'Mağazadan götürmə',
        'STANDARD_FAST' => 'Sürətli çatdırılma',
        default => 'Kuryer',
    };
@endphp

<x-storefront.layouts.app :title="'Ödəniş — ShopEra'">
    <x-storefront.breadcrumb title="Ödəniş" />

    <div class="checkout-wrapper section-padding fix">
        <div class="container">
            @if (session('status'))
                <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="sf-alert sf-alert--error">{{ session('error') }}</div>
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

            @guest
                <div class="row justify-content-center py-5">
                    <div class="col-lg-6 text-center">
                        <div class="card shadow-sm border-0 p-5 rounded-4">
                            <i class="fa-light fa-user-lock text-danger display-4 mb-3"></i>
                            <h3 class="fw-bold mb-2">Giriş tələb olunur</h3>
                            <p class="text-muted mb-4">Çatdırılma ünvanını seçmək və sifarişi tamamlamaq üçün hesabınıza daxil olun.</p>
                            <a href="{{ route('storefront.login') }}" class="theme-btn">Daxil ol</a>
                        </div>
                    </div>
                </div>
            @elseif (count($items) === 0)
                <div class="text-center py-5">
                    <p class="text-muted mb-3">Səbətiniz boşdur.</p>
                    <a href="/shop" class="theme-btn">Elanlara bax</a>
                </div>
            @else
                <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="sf-checkout-section">
                                <h3 class="h4 fw-bold mb-3">Çatdırılma üsulu</h3>

                                <div class="sf-method-grid">
                                    @foreach ([
                                        'STANDARD' => ['Kuryer çatdırılması', '1-2 iş günü', 'fa-solid fa-truck-fast'],
                                        'STANDARD_FAST' => ['Sürətli çatdırılma', '2-4 saat', 'fa-solid fa-bolt'],
                                        'PICKUP_POINT' => ['Təhvil məntəqəsi', count($pickupPoints).' filial', 'fa-solid fa-store'],
                                        'TAKE_FROM_STORE' => ['Mağazadan götürmə', 'Pulsuz', 'fa-solid fa-bag-shopping'],
                                    ] as $method => [$label, $hint, $icon])
                                        <a href="{{ route('storefront.checkout', array_filter(['delivery' => $method, 'address' => $addressId, 'promo' => $promoCode])) }}"
                                           class="sf-method-card {{ $deliveryType === $method ? 'is-active' : '' }}">
                                            <div class="sf-method-card__icon"><i class="{{ $icon }}"></i></div>
                                            <div class="sf-method-card__text">
                                                <span class="sf-method-card__title">{{ $label }}</span>
                                                <span class="sf-method-card__hint">{{ $hint }}</span>
                                            </div>
                                            <i class="fa-solid fa-circle-check sf-method-card__check"></i>
                                        </a>
                                    @endforeach
                                </div>

                                @if ($deliveryType === 'STANDARD' || $deliveryType === 'STANDARD_FAST')
                                    <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
                                        <h5 class="fw-bold mb-0">Çatdırılma ünvanı</h5>
                                    </div>

                                    @if (count($addresses))
                                        <form action="{{ route('storefront.checkout') }}" method="GET" class="mb-4">
                                            <input type="hidden" name="delivery" value="{{ $deliveryType }}">
                                            @if ($promoCode)
                                                <input type="hidden" name="promo" value="{{ $promoCode }}">
                                            @endif
                                            <label class="form-label small fw-semibold text-muted text-uppercase mb-2" for="checkout_address_select">Ünvan seçin</label>
                                            <div class="d-flex gap-2">
                                                <select id="checkout_address_select" name="address" class="form-select" onchange="this.form.submit()">
                                                    @foreach ($addresses as $address)
                                                        <option value="{{ $address['id'] }}" @selected((int) $address['id'] === (int) $addressId)>
                                                            {{ $address['location_label'] ?: $address['full_name'] }} — {{ collect([$address['street_building_number'] ?? null, $address['town_village_district'] ?? null, $address['city'] ?? null])->filter()->join(', ') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <noscript><button type="submit" class="theme-btn">Seç</button></noscript>
                                            </div>
                                        </form>

                                        @if ($selectedAddress)
                                            <div class="sf-selected-address">
                                                <i class="fa-light fa-location-dot"></i>
                                                <div>
                                                    <strong>{{ $selectedAddress['full_name'] }}</strong>
                                                    <span>
                                                        {{ $selectedAddress['contact_number'] }} ·
                                                        {{ $selectedAddress['street_building_number'] }}{{ ! empty($selectedAddress['unit_floor_apartment']) ? ', '.$selectedAddress['unit_floor_apartment'] : '' }}{{ ! empty($selectedAddress['town_village_district']) ? ', '.$selectedAddress['town_village_district'] : '' }}, {{ $selectedAddress['city'] }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <div class="sf-address-warning mb-4">
                                            <i class="fa-light fa-circle-exclamation"></i>
                                            <div>
                                                <strong>Çatdırılma ünvanı tapılmadı.</strong>
                                                <span>Sifariş verməzdən əvvəl aşağıdan ünvan əlavə edin.</span>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="sf-checkout-card mb-4">
                                        <h5 class="fw-bold mb-3">Yeni ünvan</h5>
                                        <div class="sf-form-rows">
                                            <div class="sf-form-row">
                                                <label><span>Ad, soyad *</span><input type="text" form="sf-checkout-address-form" name="full_name" value="{{ old('full_name') }}" required maxlength="255"></label>
                                                <label><span>Telefon *</span><input type="tel" form="sf-checkout-address-form" name="contact_number" value="{{ old('contact_number') }}" required placeholder="0501234567"></label>
                                            </div>
                                            <div class="sf-form-row">
                                                <label>
                                                    <span>Şəhər *</span>
                                                    <select form="sf-checkout-address-form" name="city" required>
                                                        @foreach ($cities as $city)
                                                            <option value="{{ $city['key'] ?? $city['name'] }}" @selected(old('city') === ($city['key'] ?? $city['name']))>
                                                                {{ $city['name'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <label><span>Rayon *</span><input type="text" form="sf-checkout-address-form" name="town_village_district" value="{{ old('town_village_district') }}" required maxlength="255"></label>
                                            </div>
                                            <div class="sf-form-row">
                                                <label><span>Küçə və bina *</span><input type="text" form="sf-checkout-address-form" name="street_building_number" value="{{ old('street_building_number') }}" required maxlength="255"></label>
                                                <label><span>Mənzil / mərtəbə *</span><input type="text" form="sf-checkout-address-form" name="unit_floor_apartment" value="{{ old('unit_floor_apartment') }}" required maxlength="255"></label>
                                            </div>
                                            <label><span>Ünvan etiketi (istəyə bağlı)</span><input type="text" form="sf-checkout-address-form" name="location_label" value="{{ old('location_label') }}" placeholder="Ev, iş yeri…" maxlength="255"></label>
                                        </div>
                                        <button type="submit" form="sf-checkout-address-form" class="theme-btn btn-sm mt-2">Ünvanı saxla</button>
                                    </div>
                                @elseif ($deliveryType === 'PICKUP_POINT')
                                    <div class="mt-4 mb-2">
                                        <h5 class="fw-bold mb-3">Təhvil məntəqəsini seçin</h5>
                                        @if (count($pickupPoints) === 0)
                                            <div class="sf-alert sf-alert--error">Aktiv təhvil məntəqəsi tapılmadı.</div>
                                        @else
                                            <div class="sf-pickup-grid">
                                                @foreach ($pickupPoints as $point)
                                                    <a href="{{ route('storefront.checkout', array_filter(['delivery' => 'PICKUP_POINT', 'pickup' => $point['id'], 'promo' => $promoCode])) }}"
                                                       class="sf-pickup-card {{ (int) $point['id'] === (int) $pickupPointId ? 'is-active' : '' }}">
                                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                                            <strong>{{ $point['name'] }}</strong>
                                                            <span class="badge {{ (float) ($point['price'] ?? 0) === 0.0 ? 'bg-success-subtle text-success' : 'bg-light text-dark' }}">
                                                                {{ (float) ($point['price'] ?? 0) === 0.0 ? 'Pulsuz' : $money($point['price']) }}
                                                            </span>
                                                        </div>
                                                        <p class="mb-1"><i class="fa-solid fa-location-dot text-danger me-1"></i>{{ $point['address'] ?? '' }}</p>
                                                        @if (! empty($point['delivery_time']))
                                                            <div class="small text-muted"><i class="fa-regular fa-clock me-1"></i>{{ $point['delivery_time'] }}</div>
                                                        @endif
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="sf-checkout-card mt-4 mb-4">
                                        <h5 class="fw-bold mb-1">Mərkəzi mağaza</h5>
                                        <p class="text-muted mb-2"><i class="fa-solid fa-location-dot text-danger me-1"></i>Bakı şəhəri, Nizami küçəsi 45, ShopEra showroom</p>
                                        <div class="small text-muted mb-2"><i class="fa-regular fa-clock me-1"></i>İş saatları: hər gün 10:00 – 21:00</div>
                                        <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-check me-1"></i> 0.00 ₼ — Pulsuz</span>
                                    </div>
                                @endif

                                <label class="sf-form-label mt-4">
                                    <span>Sifariş qeydi (istəyə bağlı)</span>
                                    <textarea form="sf-order-form" name="note" rows="3" placeholder="Çatdırılma üçün xüsusi qeydlər…">{{ old('note') }}</textarea>
                                </label>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="sf-checkout-summary">
                                <h4 class="fw-bold mb-3">Sifarişin xülasəsi</h4>

                                <div class="sf-checkout-items">
                                    @foreach ($items as $item)
                                        @php
                                            $product = $item['product'] ?? null;
                                            $image = $product['images'][0]['image_path'] ?? $fallback;
                                            $title = $product['title'] ?? 'Məhsul';
                                        @endphp
                                        <div class="sf-checkout-item">
                                            <img src="{{ $image }}" alt="{{ $title }}" width="46" height="46">
                                            <div class="sf-checkout-item__text">
                                                <strong>{{ $title }}</strong>
                                                <span>{{ $item['quantity'] }} × {{ $money($item['retail_unit_price'] ?? 0) }}</span>
                                            </div>
                                            <span class="sf-checkout-item__total">{{ $money($item['retail_total'] ?? 0) }}</span>
                                        </div>
                                    @endforeach
                                </div>

                                <form action="{{ route('storefront.checkout') }}" method="GET" class="sf-promo-form">
                                    <input type="hidden" name="delivery" value="{{ $deliveryType }}">
                                    <input type="hidden" name="address" value="{{ $addressId }}">
                                    <input type="hidden" name="pickup" value="{{ $pickupPointId }}">
                                    <input type="text" name="promo" value="{{ $promoCode }}" placeholder="Promo kodunuz var?">
                                    <button type="submit" class="sf-btn sf-btn--ghost">Tətbiq et</button>
                                </form>

                                <div class="sf-price-breakdown">
                                    <div class="sf-price-row">
                                        <span>Ara cəm</span>
                                        <span>{{ $money($preview['main_amount'] ?? $total) }}</span>
                                    </div>
                                    @if (! empty($preview['discount_amount']) && (float) $preview['discount_amount'] > 0)
                                        <div class="sf-price-row is-discount">
                                            <span>Endirim</span>
                                            <span>-{{ $money($preview['discount_amount']) }}</span>
                                        </div>
                                    @endif
                                    <div class="sf-price-row">
                                        <span>Çatdırılma <small class="text-muted">({{ $deliveryLabel }})</small></span>
                                        <span>
                                            @if ($preview === null && $previewError)
                                                <span class="text-muted small">Hesablanmadı</span>
                                            @elseif ($preview !== null && (float) ($preview['delivery'] ?? 0) > 0)
                                                {{ $money($preview['delivery']) }}
                                            @else
                                                <span class="text-success fw-bold">Pulsuz</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="sf-price-row is-total">
                                        <span>Cəm</span>
                                        <span>{{ $money($preview['total'] ?? $total) }}</span>
                                    </div>
                                </div>

                                @if ($previewError)
                                    <div class="sf-alert sf-alert--error py-2 small">{{ $previewError }}</div>
                                @endif

                                <h6 class="fw-bold mb-3 mt-2">Ödəniş üsulu</h6>
                                <div class="d-flex flex-column gap-2">
                                    <label class="sf-payment-option">
                                        <input type="radio" form="sf-order-form" name="payment_type" value="CARD" checked>
                                        <span class="sf-payment-option__body">
                                            <strong>Onlayn kart ödənişi</strong>
                                            <small>Visa, Mastercard</small>
                                        </span>
                                        <span class="text-muted fs-5"><i class="fa-brands fa-cc-visa text-primary"></i> <i class="fa-brands fa-cc-mastercard text-danger"></i></span>
                                    </label>
                                    <label class="sf-payment-option">
                                        <input type="radio" form="sf-order-form" name="payment_type" value="CASH">
                                        <span class="sf-payment-option__body">
                                            <strong>Çatdırılma zamanı nağd</strong>
                                            <small>Məhsulu alarkən ödəyin</small>
                                        </span>
                                        <span class="text-success fs-5"><i class="fa-light fa-money-bill-wave"></i></span>
                                    </label>
                                </div>

                                <button type="submit" form="sf-order-form" class="theme-btn w-100 mt-3">
                                    <i class="fa-solid fa-lock me-2"></i> Sifarişi tamamla
                                </button>

                                <p class="text-center small text-muted mt-3 mb-0">
                                    <i class="fa-light fa-shield-check text-success me-1"></i> Təhlükəsiz ödəniş
                                </p>
                            </div>
                        </div>
                    </div>

                <form id="sf-order-form" action="{{ route('storefront.checkout.place') }}" method="POST">
                    @csrf
                    <input type="hidden" name="address_type" value="{{ $deliveryType }}">
                    <input type="hidden" name="address_type_id" value="{{ $addressTypeId }}">
                    <input type="hidden" name="promo_code" value="{{ $promoCode }}">
                </form>

                <form action="{{ route('storefront.checkout.whatsapp') }}" method="GET" class="text-center mt-3">
                    <button type="submit" class="sf-whatsapp-btn">
                        <i class="fa-brands fa-whatsapp"></i> WhatsApp ilə sifariş et
                    </button>
                </form>

                <form id="sf-checkout-address-form" action="{{ route('storefront.checkout.address') }}" method="POST">
                    @csrf
                </form>
            @endguest
        </div>
    </div>
</x-storefront.layouts.app>
