<x-storefront.layouts.app
    :title="'Əlaqə — ShopEra'"
    :description="'ShopEra ilə əlaqə: ünvan, telefon, e-poçt və müraciət forması.'"
    :categories="$categories">

    <x-storefront.breadcrumb title="Əlaqə" />

    <div class="container sf-contact">
        <header class="sf-blog__head">
            <h1>Bizimlə əlaqə</h1>
            <p>Sualınız var? Yazın, tezliklə cavab verəcəyik.</p>
        </header>

        <div class="sf-contact__grid">
            <aside class="sf-contact__info">
                <div class="sf-contact-card">
                    <i class="fa-regular fa-location-dot"></i>
                    <div>
                        <span>Ünvan</span>
                        <strong>{{ $contactInfo['address'] }}</strong>
                    </div>
                </div>

                <div class="sf-contact-card">
                    <i class="fa-regular fa-envelope"></i>
                    <div>
                        <span>E-poçt</span>
                        <strong><a href="mailto:{{ $contactInfo['email'] }}">{{ $contactInfo['email'] }}</a></strong>
                    </div>
                </div>

                @if (! empty($contactInfo['phones']))
                    <div class="sf-contact-card">
                        <i class="fa-regular fa-phone"></i>
                        <div>
                            <span>Telefon</span>
                            @foreach ($contactInfo['phones'] as $phone)
                                <strong><a href="tel:{{ $phone }}">{{ $phone }}</a></strong>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (! empty($contactInfo['whatsapp_number']))
                    <div class="sf-contact-card">
                        <i class="fa-brands fa-whatsapp"></i>
                        <div>
                            <span>WhatsApp</span>
                            <strong><a href="https://wa.me/{{ preg_replace('/\D+/', '', $contactInfo['whatsapp_number']) }}" target="_blank" rel="noopener">{{ $contactInfo['whatsapp_number'] }}</a></strong>
                        </div>
                    </div>
                @endif

                @php
                    $socials = collect([
                        'instagram_url' => ['Instagram', 'fa-brands fa-instagram'],
                        'facebook_url' => ['Facebook', 'fa-brands fa-facebook-f'],
                        'twitter_url' => ['X', 'fa-brands fa-twitter'],
                        'youtube_url' => ['YouTube', 'fa-brands fa-youtube'],
                        'telegram_url' => ['Telegram', 'fa-brands fa-telegram'],
                        'linkedin_url' => ['LinkedIn', 'fa-brands fa-linkedin-in'],
                        'tiktok_url' => ['TikTok', 'fa-brands fa-tiktok'],
                    ])->filter(fn ($item, $key) => ! empty($contactInfo[$key]));
                @endphp

                @if ($socials->isNotEmpty())
                    <div class="sf-contact__socials">
                        @foreach ($socials as $key => [$label, $icon])
                            <a href="{{ $contactInfo[$key] }}" target="_blank" rel="noopener nofollow" aria-label="{{ $label }}">
                                <i class="{{ $icon }}"></i>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if (! empty($contactInfo['google_map_url']))
                    <a class="sf-btn sf-btn--ghost" href="{{ $contactInfo['google_map_url'] }}" target="_blank" rel="noopener">
                        <i class="fa-regular fa-map"></i> Xəritədə bax
                    </a>
                @endif
            </aside>

            <div class="sf-contact__form">
                @if (session('status'))
                    <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
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

                <form action="{{ route('storefront.contact.send') }}" method="POST">
                    @csrf

                    <div class="sf-form-row">
                        <label>
                            <span>Ad *</span>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required maxlength="100">
                        </label>
                        <label>
                            <span>Soyad</span>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" maxlength="100">
                        </label>
                    </div>

                    <div class="sf-form-row">
                        <label>
                            <span>E-poçt *</span>
                            <input type="email" name="email" value="{{ old('email') }}" required maxlength="150">
                        </label>
                        <label>
                            <span>Telefon</span>
                            <input type="text" name="phone" value="{{ old('phone') }}" maxlength="30">
                        </label>
                    </div>

                    <label>
                        <span>Mövzu</span>
                        <input type="text" name="subject" value="{{ old('subject') }}" maxlength="150">
                    </label>

                    <label>
                        <span>Mesaj *</span>
                        <textarea name="message" rows="6" required maxlength="2000">{{ old('message') }}</textarea>
                    </label>

                    <button type="submit" class="sf-btn sf-btn--primary">Mesajı göndər</button>
                </form>
            </div>
        </div>
    </div>
</x-storefront.layouts.app>
