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
    $vendorName = $localized($invite['vendor']['name'] ?? '');
@endphp

<x-storefront.layouts.app :title="'Mağazaya dəvət — ShopEra'">
    <div class="section-padding fix elan-page">
        <div class="container" style="max-width: 560px;">
            <div class="sf-panel">
                <h4 class="fw-bold mb-2">Mağazaya dəvət</h4>
                <p class="text-muted">
                    <strong>{{ $vendorName }}</strong> mağazası sizi komandaya dəvət edir.
                </p>

                <p class="mb-2">Sizə veriləcək icazələr:</p>
                <ul class="mb-4">
                    @foreach (($invite['permissions'] ?? []) as $key)
                        <li>{{ $labels[$key] ?? $key }}</li>
                    @endforeach
                </ul>

                @if ($errors->any())
                    <div class="sf-alert sf-alert--error">{{ $errors->first() }}</div>
                @endif

                @auth
                    <form action="{{ route('storefront.store.team.accept', $token) }}" method="POST">
                        @csrf
                        <button type="submit" class="theme-btn">Dəvəti qəbul et</button>
                        <a href="/" class="sf-btn sf-btn--ghost">İmtina</a>
                    </form>
                @else
                    <p class="text-muted small">
                        Dəvəti qəbul etmək üçün hesabınıza daxil olun.
                    </p>
                    <a href="{{ route('storefront.login') }}" class="theme-btn">Daxil ol</a>
                @endauth
            </div>
        </div>
    </div>
</x-storefront.layouts.app>
