@php
    $fallback = '/assets/images/top-deals-item/topDealsItemThumb1_1.png';
    $money = fn ($value) => number_format((float) $value, 2).' ₼';
    $deliveryLabels = [
        'STANDARD' => 'Kuryer çatdırılması',
        'STANDARD_FAST' => 'Sürətli çatdırılma',
        'PICKUP_POINT' => 'Təhvil məntəqəsi',
        'TAKE_FROM_STORE' => 'Mağazadan götürmə',
    ];
@endphp

<x-storefront.layouts.app :title="'Sifariş tarixçəsi — ShopEra'">
    <x-storefront.breadcrumb title="Sifariş tarixçəsi" />

    <div class="sf-orders section-padding fix bg-white">
        <div class="container">
            @if (session('status'))
                <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
            @endif

            <header class="sf-orders__head">
                <div>
                    <h1 class="sf-orders__title">Sifariş tarixçəsi</h1>
                    <p class="sf-orders__count">{{ count($orders) }} sifariş</p>
                </div>
            </header>

            @if (count($orders) === 0)
                <div class="text-center py-5">
                    <div class="empty-icon mb-3"><i class="fa-light fa-bag-shopping"></i></div>
                    <h4 class="fw-bold mb-2">Hələ sifarişiniz yoxdur</h4>
                    <p class="text-muted mb-4">İlk sifarişinizi verdikdən sonra burada görünəcək.</p>
                    <a href="/shop" class="theme-btn">Elanlara bax</a>
                </div>
            @else
                <div class="sf-orders__list">
                    @foreach ($orders as $order)
                        @php
                            $items = $order['items'] ?? [];
                            $status = $order['latest_status']['status'] ?? 'Yeni';
                            $statusKey = $order['latest_status']['status_key'] ?? 'pending';
                        @endphp
                        <article class="sf-order-card">
                            <header class="sf-order-card__head">
                                <div class="sf-order-card__meta">
                                    <span class="sf-order-card__id">#{{ $order['id'] }}</span>
                                    <span class="sf-order-card__date">
                                        <i class="fa-regular fa-calendar"></i>
                                        {{ \Illuminate\Support\Carbon::parse($order['created_at'])->translatedFormat('d M, Y · H:i') }}
                                    </span>
                                </div>
                                <span class="sf-order-status sf-order-status--{{ $statusKey }}">{{ $status }}</span>
                            </header>

                            @if ($items)
                                <div class="sf-order-card__items">
                                    @foreach (array_slice($items, 0, 4) as $item)
                                        @php
                                            $product = $item['product'] ?? null;
                                            $image = $product['images'][0]['image_path'] ?? $fallback;
                                            $title = $product['title'] ?? 'Məhsul';
                                        @endphp
                                        <a href="{{ $product ? (! empty($product['slug']) ? '/elan/'.$product['slug'] : '/shop') : '/shop' }}" class="sf-order-card__item">
                                            <img src="{{ $image }}" alt="{{ $title }}" loading="lazy">
                                            <span class="sf-order-card__item-text">
                                                <strong>{{ $title }}</strong>
                                                <span>{{ $item['quantity'] }} × {{ $money($item['unit_price'] ?? 0) }}</span>
                                            </span>
                                        </a>
                                    @endforeach
                                    @if (count($items) > 4)
                                        <span class="sf-order-card__more">+{{ count($items) - 4 }} məhsul</span>
                                    @endif
                                </div>
                            @endif

                            <footer class="sf-order-card__foot">
                                <div class="sf-order-card__facts">
                                    @if (! empty($deliveryLabels[$order['address_type'] ?? '']))
                                        <span><i class="fa-light fa-truck-fast"></i> {{ $deliveryLabels[$order['address_type']] }}</span>
                                    @endif
                                    @if (! empty($order['transaction_id']))
                                        <span><i class="fa-light fa-receipt"></i> {{ $order['transaction_id'] }}</span>
                                    @endif
                                </div>
                                <div class="sf-order-card__total">
                                    <span>Cəm</span>
                                    <strong>{{ $money($order['total_price'] ?? 0) }}</strong>
                                </div>
                            </footer>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-storefront.layouts.app>
