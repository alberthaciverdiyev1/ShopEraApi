@php
    $fallback = '/assets/images/top-deals-item/topDealsItemThumb1_1.png';
    $money = fn ($value) => number_format((float) $value, 2).' ₼';
@endphp

<x-storefront.layouts.app :title="'Səbət — ShopEra'">
    <x-storefront.breadcrumb title="Səbət" />

    <div class="cart-wrapper section-padding fix bg-white">
        <div class="container">
            @if (session('status'))
                <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="sf-alert sf-alert--error">{{ session('error') }}</div>
            @endif

            @guest
                <div class="text-center py-5">
                    <p class="text-muted mb-3">Səbətinizi görmək üçün daxil olun.</p>
                    <a href="{{ route('storefront.login') }}" class="theme-btn">Daxil ol</a>
                </div>
            @elseif (count($items) === 0)
                <div class="text-center py-5">
                    <div class="wishlist-empty-state py-5">
                        <div class="empty-icon mb-3">
                            <i class="fa-light fa-cart-shopping"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Səbətiniz boşdur</h4>
                        <p class="text-muted mb-4">Elanlara baxaraq bəyəndiyiniz məhsulları səbətə əlavə edə bilərsiniz.</p>
                        <a href="/shop" class="theme-btn">Alış-verişə davam et</a>
                    </div>
                </div>
            @else
                <div class="row g-4 align-items-start">
                    <div class="col-lg-8 col-12">
                        <div class="sf-cart-table">
                            <div class="sf-cart-head">
                                <span>Məhsul</span>
                                <span>Qiymət</span>
                                <span>Miqdar</span>
                                <span>Cəm</span>
                                <span></span>
                            </div>

                            <div class="sf-cart-list">
                                @foreach ($items as $item)
                                    @php
                                        $product = $item['product'] ?? null;
                                        $title = $product['title'] ?? 'Məhsul';
                                        $image = $product['images'][0]['image_path'] ?? $fallback;
                                        $unit = (float) ($item['retail_unit_price'] ?? 0);
                                        $lineTotal = (float) ($item['retail_total'] ?? 0);
                                    @endphp
                                    <div class="sf-cart-row">
                                        <div class="sf-cart-product">
                                            <a href="{{ $product ? (! empty($product['slug']) ? '/elan/'.$product['slug'] : '/shop') : '/shop' }}" class="sf-cart-thumb">
                                                <img src="{{ $image }}" alt="{{ $title }}" loading="lazy">
                                            </a>
                                            <div class="sf-cart-info">
                                                <a class="sf-cart-name" href="{{ $product ? (! empty($product['slug']) ? '/elan/'.$product['slug'] : '/shop') : '/shop' }}">{{ $title }}</a>
                                                @if (! empty($item['color_id']) || ! empty($item['size_id']))
                                                    <div class="sf-cart-variants">
                                                        @if (! empty($item['color_id']))
                                                            <span class="sf-cart-chip">Rəng #{{ $item['color_id'] }}</span>
                                                        @endif
                                                        @if (! empty($item['size_id']))
                                                            <span class="sf-cart-chip"><i class="fa-light fa-ruler-combined"></i> Ölçü #{{ $item['size_id'] }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="sf-cart-price">{{ $money($unit) }}</div>

                                        <div class="sf-cart-qty">
                                            <form action="{{ route('storefront.cart.update', $item['id']) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="quantity" value="{{ max(1, (int) $item['quantity'] - 1) }}">
                                                <button type="submit" aria-label="Miqdarı azalt" @disabled($item['quantity'] <= 1)>
                                                    <i class="fa fa-minus" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                            <input class="form-control" type="number" value="{{ $item['quantity'] }}" readonly aria-label="Miqdar">
                                            <form action="{{ route('storefront.cart.update', $item['id']) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="quantity" value="{{ (int) $item['quantity'] + 1 }}">
                                                <button type="submit" aria-label="Miqdarı artır">
                                                    <i class="fa fa-plus" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <div class="sf-cart-total">{{ $money($lineTotal) }}</div>

                                        <form class="sf-cart-remove-form" action="{{ route('storefront.cart.destroy', $item['id']) }}" method="POST"
                                              onsubmit="return confirm('Məhsul səbətdən çıxarılsın?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sf-cart-remove" aria-label="Məhsulu sil" title="Sil">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-12">
                        <div class="sf-cart-totals">
                            <h3 class="sf-cart-totals__title">Səbətin cəmi</h3>
                            <div class="sf-cart-totals__body">
                                <div class="sf-cart-totals__row">
                                    <span>Ara cəm</span>
                                    <span>{{ $money($total) }}</span>
                                </div>
                                <div class="sf-cart-totals__divider"></div>
                                <div class="sf-cart-totals__row is-total">
                                    <span>Cəm</span>
                                    <span>{{ $money($total) }}</span>
                                </div>
                            </div>
                            <a href="{{ route('storefront.checkout') }}" class="theme-btn w-100">Ödənişə keç</a>
                        </div>
                    </div>
                </div>
            @endguest
        </div>
    </div>
</x-storefront.layouts.app>
