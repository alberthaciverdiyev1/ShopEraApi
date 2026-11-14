@php
    $localized = function ($value): string {
        if (is_array($value)) {
            return $value['az'] ?? $value['en'] ?? (string) reset($value);
        }

        return (string) ($value ?? '');
    };

    $storeName = $localized($vendor['name'] ?? '');
    $initial = mb_strtoupper(mb_substr($storeName ?: 'M', 0, 1));
    $granted = $vendor['permissions'] ?? [];

    // Where each permission leads inside the store workspace.
    $workspace = [
        'listings' => ['url' => '/magaza/panel#listings', 'cta' => 'Elanlara bax'],
        'promotions' => ['url' => '/magaza/panel#listings', 'cta' => 'Elanlara bax'],
        'stats' => ['url' => '/magaza/panel#overview', 'cta' => 'Statistikaya bax'],
        'settings' => ['url' => '/magaza/panel#settings', 'cta' => 'Məlumatları redaktə et'],
    ];
@endphp

<x-storefront.layouts.app :title="'Komanda — '.$storeName.' — ShopEra'">
    <div class="section-padding fix elan-page">
        <div class="container" style="max-width: 860px;">
            <div class="sf-panel mb-4">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0 overflow-hidden"
                          style="width: 64px; height: 64px; font-size: 24px;">
                        @if (! empty($vendor['logo_url']))
                            <img src="{{ $vendor['logo_url'] }}" alt="{{ $storeName }}"
                                 style="width: 64px; height: 64px; object-fit: cover;">
                        @else
                            {{ $initial }}
                        @endif
                    </span>
                    <div class="min-w-0 flex-grow-1">
                        <h4 class="fw-bold mb-1">{{ $storeName }}</h4>
                        <p class="text-muted mb-0 small">
                            Siz bu mağazanın komandasındasınız.
                            İcazələri yalnız mağaza sahibi dəyişə bilər.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="sf-btn sf-btn--ghost" href="/magaza/panel">Mağaza paneli</a>
                        @if (! empty($vendor['slug']))
                            <a class="sf-btn sf-btn--ghost" href="/magaza/{{ $vendor['slug'] }}" target="_blank" rel="noopener">
                                Mağaza səhifəsi
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            @if (count($granted) === 0)
                <div class="sf-alert sf-alert--error">
                    Sizə hələ heç bir icazə verilməyib. Mağaza sahibi ilə əlaqə saxlayın.
                </div>
            @endif

            <div class="sf-panel">
                <h6 class="fw-bold mb-1">İcazələriniz</h6>
                <p class="text-muted small mb-3">
                    Yaşıl işarəli bölmələrə girişiniz var; boz olanlar üçün mağaza sahibindən icazə istəyin.
                </p>

                <div class="row g-3">
                    @foreach ($permissions as $permission)
                        @php
                            $has = in_array($permission['key'], $granted, true);
                            $target = $workspace[$permission['key']] ?? null;
                        @endphp
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 d-flex flex-column {{ $has ? '' : 'bg-light' }}">
                                <div class="d-flex align-items-start gap-2 mb-1">
                                    <i class="fa-solid {{ $has ? 'fa-circle-check text-success' : 'fa-circle-xmark text-muted' }} mt-1"></i>
                                    <div class="min-w-0">
                                        <span class="d-block fw-semibold">{{ $permission['label'] }}</span>
                                        <span class="d-block text-muted small">{{ $permission['description'] ?? '' }}</span>
                                    </div>
                                </div>
                                @if ($has && $target)
                                    <a href="{{ $target['url'] }}" class="small ms-4 mt-auto">{{ $target['cta'] }} →</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="sf-panel mt-4">
                <h6 class="fw-bold mb-2">Mağaza ilə əlaqə</h6>
                @if (! empty($vendor['phone']) || ! empty($vendor['email']))
                    <p class="text-muted small mb-2">
                        İcazə dəyişikliyi üçün mağaza sahibinə müraciət edin:
                    </p>
                    <ul class="mb-0 small">
                        @if (! empty($vendor['phone']))
                            <li>Telefon: <a href="tel:{{ $vendor['phone'] }}">{{ $vendor['phone'] }}</a></li>
                        @endif
                        @if (! empty($vendor['email']))
                            <li>E-poçt: <a href="mailto:{{ $vendor['email'] }}">{{ $vendor['email'] }}</a></li>
                        @endif
                    </ul>
                @else
                    <p class="text-muted small mb-0">
                        Mağaza üçün əlaqə məlumatı göstərilməyib. İcazə dəyişikliyi üçün sahiblə birbaşa əlaqə saxlayın.
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-storefront.layouts.app>
