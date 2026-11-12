<x-storefront.layouts.app :title="'Parametrlər — ShopEra'">
    <x-storefront.breadcrumb title="Parametrlər" parent="Hesabım" />

    <div class="section-padding fix account-dashboard">
        <div class="container">
            <div class="sf-panel">
                <h2 class="sf-panel__title">Hesab parametrləri</h2>

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

                @include('storefront.settings._form', ['user' => $user])
            </div>
        </div>
    </div>
</x-storefront.layouts.app>
