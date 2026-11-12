<x-storefront.layouts.app
    :title="'Qeydiyyat — ShopEra'"
    :description="'ShopEra-da pulsuz hesab yaradın.'"
    :categories="$categories">

    <section class="sf-auth-page">
        <div class="container">
            <div class="sf-auth-card">
                <div class="sf-auth-form">
                    <h1 class="sf-auth-title">İndi başlayın</h1>
                    <p class="sf-auth-subtitle">Hesab yaratmaq üçün məlumatlarınızı yazın.</p>

                    @if ($errors->any())
                        <div class="sf-alert sf-alert--error">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('storefront.register.store') }}" method="POST" class="sf-auth-fields">
                        @csrf

                        <label class="sf-auth-field">
                            <span>Adınız *</span>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   placeholder="Adınızı yazın" required autofocus>
                        </label>

                        <label class="sf-auth-field">
                            <span>Telefon nömrəsi *</span>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                   placeholder="Telefon nömrənizi yazın" required>
                        </label>

                        <label class="sf-auth-field">
                            <span>E-poçt ünvanı</span>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   placeholder="E-poçt ünvanınız (istəyə bağlı)">
                        </label>

                        <label class="sf-auth-field">
                            <span>Şifrə *</span>
                            <span class="sf-auth-password">
                                <input type="password" name="password" placeholder="********" required minlength="6" data-sf-password>
                                <button type="button" class="sf-auth-password__toggle" data-sf-password-toggle
                                        aria-label="Şifrəni göstər">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </span>
                        </label>

                        <label class="sf-auth-field">
                            <span>Şifrə (təkrar) *</span>
                            <input type="password" name="password_confirmation" placeholder="********" required minlength="6">
                        </label>

                        <button type="submit" class="sf-btn sf-btn--primary sf-auth-submit">Qeydiyyatdan keç</button>
                    </form>

                    <p class="sf-auth-switch">
                        Hesabınız var?
                        <a href="{{ route('storefront.login') }}">Daxil olun</a>
                    </p>
                </div>

                <div class="sf-auth-thumb">
                    <img src="/assets/images/register/loginThumb.jpg" alt="ShopEra">
                </div>
            </div>
        </div>
    </section>
</x-storefront.layouts.app>
