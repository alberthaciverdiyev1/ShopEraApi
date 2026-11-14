@php
    $digits = preg_replace('/\D+/', '', (string) ($whatsapp ?? ''));
    $waText = rawurlencode('Salam, ShopEra hesabımın şifrəsini bərpa etmək istəyirəm. Hesab: ');
    $waUrl = $digits !== '' ? 'https://wa.me/'.$digits.'?text='.$waText : null;
@endphp

<x-storefront.layouts.app
    :title="'Şifrə bərpası — ShopEra'"
    :description="'ShopEra hesabınızın şifrəsini WhatsApp vasitəsilə bərpa edin.'"
    :categories="$categories">

    <section class="sf-auth-page">
        <div class="container">
            <div class="sf-auth-card">
                <div class="sf-auth-form">
                    <h1 class="sf-auth-title">Şifrə bərpası</h1>
                    <p class="sf-auth-subtitle">
                        Təhlükəsizlik üçün şifrə bərpası bizim komandamız tərəfindən WhatsApp vasitəsilə aparılır.
                    </p>

                    <ol class="sf-auth-steps">
                        <li>
                            <strong>WhatsApp ilə yazın.</strong>
                            Aşağıdakı düyməni basın — hazır mesajla söhbət açılacaq.
                        </li>
                        <li>
                            <strong>Hesabınızı göstərin.</strong>
                            Mesajda qeydiyyatdan keçdiyiniz telefon nömrəsini və ya e-poçt ünvanını yazın.
                        </li>
                        <li>
                            <strong>Yeni şifrəni alın.</strong>
                            Komandamız məlumatı yoxladıqdan sonra sizə müvəqqəti şifrə göndərəcək.
                        </li>
                    </ol>

                    @if ($waUrl)
                        <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="sf-btn sf-btn--primary sf-auth-whatsapp">
                            <i class="fa-brands fa-whatsapp"></i> WhatsApp ilə yaz
                        </a>
                    @else
                        <div class="sf-alert sf-alert--error">
                            WhatsApp nömrəsi hələ təyin olunmayıb.
                            @if ($supportEmail)
                                Bizimlə {{ $supportEmail }} ünvanı ilə əlaqə saxlayın.
                            @endif
                        </div>
                    @endif

                    <p class="sf-auth-switch">
                        Şifrənizi xatırladınız?
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
