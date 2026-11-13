<x-storefront.layouts.app :title="'İstək siyahısı — ShopEra'">
    <x-storefront.breadcrumb title="İstək siyahısı" />

    <div class="wishlist-wrapper section-padding fix bg-white">
        <div class="container">
            @if (session('status'))
                <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
            @endif

            @if (count($products) === 0)
                <div class="wishlist-empty-state text-center py-5">
                    <div class="empty-icon mb-3">
                        <i class="fa-light fa-heart-crack"></i>
                    </div>
                    <h4 class="fw-bold mb-2">İstək siyahınız boşdur</h4>
                    <p class="text-muted mb-4 mx-auto" style="max-width: 440px;">
                        Hələ heç bir məhsulu istək siyahınıza əlavə etməmisiniz. Elanlara baxaraq bəyəndiklərinizi əlavə edə bilərsiniz.
                    </p>
                    <a href="/shop" class="theme-btn btn-sm">Elanlara bax</a>
                </div>
            @else
                <div class="wishlist-header d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom flex-wrap gap-3">
                    <div>
                        <h4 class="fw-bold mb-1">Mənim istək siyahım</h4>
                        <p class="text-muted small mb-0">Toplam <strong>{{ count($products) }}</strong> məhsul</p>
                    </div>
                    <a href="/shop" class="theme-btn btn-sm btn-outline">
                        <i class="fa-regular fa-bag-shopping me-1"></i> Alış-verişə davam et
                    </a>
                </div>

                <div class="row g-2 g-sm-3 g-md-4">
                    @foreach ($products as $product)
                        <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                            <div class="sf-wishlist-item">
                                <form action="{{ route('storefront.wishlist.destroy', $product['slug'] ?? $product['id']) }}" method="POST"
                                      class="sf-wishlist-item__remove"
                                      onsubmit="return confirm('Məhsul istək siyahısından çıxarılsın?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="Siyahıdan çıxar">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </form>
                                <x-storefront.product-card :product="$product" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-storefront.layouts.app>
