<x-storefront.layouts.app
    :title="'Giriş — ShopEra'"
    :description="'ShopEra hesabınıza daxil olun.'"
    :categories="$categories">

    <section class="sf-auth-page">
        <div class="container">
            <div class="sf-auth-card">
                <div class="sf-auth-form">
                    <h1 class="sf-auth-title">İndi başlayın</h1>
                    <p class="sf-auth-subtitle">Hesabınıza daxil olmaq üçün məlumatlarınızı yazın.</p>

                    @if ($errors->any())
                        <div class="sf-alert sf-alert--error">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('storefront.login.attempt') }}" method="POST" class="sf-auth-fields">
                        @csrf

                        <label class="sf-auth-field">
                            <span>Telefon və ya e-poçt *</span>
                            <input type="text" name="identifier" value="{{ old('identifier') }}"
                                   placeholder="Telefon nömrəsi və ya e-poçt" required autofocus>
                        </label>

                        <label class="sf-auth-field">
                            <span>Şifrə *</span>
                            <span class="sf-auth-password">
                                <input type="password" name="password" placeholder="********" required data-sf-password>
                                <button type="button" class="sf-auth-password__toggle" data-sf-password-toggle
                                        aria-label="Şifrəni göstər">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </span>
                        </label>

                        <label class="sf-auth-remember">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            <span>Məni yadda saxla</span>
                        </label>

                        <button type="submit" class="sf-btn sf-btn--primary sf-auth-submit">Daxil ol</button>
                    </form>

                    <p class="sf-auth-switch">
                        Hesabınız yoxdur?
                        <a href="{{ route('storefront.register') }}">Qeydiyyatdan keçin</a>
                    </p>
                </div>

                <div class="sf-auth-thumb">
                    <img src="/assets/images/register/loginThumb.jpg" alt="ShopEra">
                </div>
            </div>
        </div>
    </section>
</x-storefront.layouts.app>
