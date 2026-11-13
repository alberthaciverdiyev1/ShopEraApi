@php
    $localized = function ($value): string {
        if (is_array($value)) {
            return $value['az'] ?? $value['en'] ?? (string) reset($value);
        }

        return (string) ($value ?? '');
    };
    $labels = [
        'listings' => 'Elanları idarə et',
        'promotions' => 'Elanları irəli çək / premium et',
        'orders' => 'Sifarişləri idarə et',
        'messages' => 'Mesajlara cavab ver',
        'stats' => 'Statistika və hesabatlar',
        'settings' => 'Mağaza məlumatlarını redaktə et',
    ];
@endphp

<x-storefront.layouts.app :title="'Komanda — '.$localized($vendor['name'] ?? '').' — ShopEra'">
    <div class="section-padding fix elan-page">
        <div class="container" style="max-width: 620px;">
            <div class="sf-panel">
                <h4 class="fw-bold mb-2">Komanda</h4>
                <p class="text-muted">
                    Siz <strong>{{ $localized($vendor['name'] ?? '') }}</strong> mağazasının komandasındasınız.
                    İcazələri yalnız mağaza sahibi dəyişə bilər.
                </p>

                <p class="mb-2 fw-semibold">İcazələriniz:</p>
                <ul class="mb-4">
                    @forelse (($vendor['permissions'] ?? []) as $key)
                        <li>{{ $labels[$key] ?? $key }}</li>
                    @empty
                        <li class="text-muted">Hələ icazə verilməyib.</li>
                    @endforelse
                </ul>

                <a class="sf-btn sf-btn--ghost" href="/magaza/panel">Mağaza paneli</a>
            </div>
        </div>
    </div>
</x-storefront.layouts.app>
