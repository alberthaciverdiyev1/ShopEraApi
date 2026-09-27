<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teymur Store - Rahat alış-veriş, bir toxunuşla</title>
    <meta name="description"
          content="Teymur Store mobil tətbiqi ilə sevdiyiniz məhsulları daha tez və asan əldə edin. İndi yükləyin və rahat alış-verişin keyfini çıxarın.">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://teymurstore.az/">
    <meta property="og:title" content="Teymur Store - Rahat alış-veriş, bir toxunuşla">
    <meta property="og:description" content="Sevdiyiniz məhsullar, daha tez və asan.">
    <meta property="og:image" content="/assets/images/logo.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://teymurstore.az/">
    <meta property="twitter:title" content="Teymur Store - Rahat alış-veriş, bir toxunuşla">
    <meta property="twitter:description" content="Sevdiyiniz məhsullar, daha tez və asan.">
    <meta property="twitter:image" content="/assets/images/logo.png">

    <!-- Favicon placeholder -->
    <link rel="icon" type="image/png" href="{{asset('assets/images/favicon.ico')}}">

    <!-- Structured Data -->
    @verbatim
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Organization",
                "name": "Teymur Store",
                "url": "https://teymurstore.az",
                "logo": "/assets/images/logo.png",
                "contactPoint": {
                    "@type": "ContactPoint",
                    "email": "info@teymurstore.az",
                    "contactType": "Customer Service"
                }
            }
        </script>
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "WebSite",
                "name": "Teymur Store",
                "url": "https://teymurstore.az"
            }
        </script>
    @endverbatim


    <link rel="stylesheet" href="{{asset('assets/styles.css')}}">
</head>
<body>
<!-- Sticky Navigation -->
<header>
    <nav class="nav" id="nav" role="navigation" aria-label="Əsas naviqasiya">
        <div class="container nav-container">
            <a href="#home" class="nav-logo" aria-label="Ana səhifəyə qayıt">
                <img src="{{asset('assets/images/logo.png')}}" alt="Teymur Store Logo" class="logo-img">
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="Menyunu aç" aria-expanded="false">
                <span class="hamburger"></span>
            </button>

            <ul class="nav-menu" id="navMenu">
                <li><a href="#home" class="nav-link active">Ana səhifə</a></li>
                <li><a href="#screenshots" class="nav-link">Ekran görüntüləri</a></li>
                <li><a href="#about" class="nav-link">Haqqımızda</a></li>
                <li><a href="#faq" class="nav-link">FAQ</a></li>
                <li><a href="#contact" class="nav-link">Əlaqə</a></li>
            </ul>
        </div>
    </nav>
</header>

<main>
    <!-- Hero Section -->
    <section id="home" class="hero" aria-labelledby="hero-title">
        <div class="container hero-container">
            <div class="hero-content">
                <h1 id="hero-title" class="hero-title">
                    <span class="hero-title-main">Teymur Store</span>
                    <span class="hero-title-sub">Rahat alış-veriş, bir toxunuşla</span>
                </h1>
                <p class="hero-text">
                    Sevdiyiniz məhsulları daha tez və asan əldə edin. Minlərlə məhsul, sürətli çatdırılma və təhlükəsiz
                    ödəniş imkanları.
                </p>
                <div class="hero-buttons">
                    <a href="https://apps.apple.com/az/app/teymur-store/id6756307921" target="_blank" rel="noopener noreferrer"
                       class="download-btn app-store" aria-label="App Store-dan yükləyin">
                        <svg viewBox="0 0 24 24" class="download-icon" aria-hidden="true">
                            <path
                                d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                        </svg>
                        <span>
                <small>Download on the</small>
                <strong>App Store</strong>
              </span>
                    </a>
                    <a href="https://play.google.com/store/apps/details?id=com.app.teymur&hl=az" target="_blank" rel="noopener noreferrer"
                       class="download-btn google-play" aria-label="Google Play-dən yükləyin">
                        <svg viewBox="0 0 24 24" class="download-icon" aria-hidden="true">
                            <path
                                d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.5,12.92 20.16,13.19L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z"/>
                        </svg>
                        <span>
                <small>GET IT ON</small>
                <strong>Google Play</strong>
              </span>
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-image-bg"></div>
                <img src="{{asset('assets/images/screen.png')}}" alt="Teymur Store mobil tətbiqi ekran görüntüsü"
                     class="hero-img">
            </div>
        </div>
    </section>

    <!-- Screenshots Section -->
    <section id="screenshots" class="screenshots" aria-labelledby="screenshots-title">
        <div class="container">
            <h2 id="screenshots-title" class="section-title">Tətbiq ekranları</h2>
            <p class="section-subtitle">İstifadəsi asan və intuitiv interfeys</p>

            <div class="screenshots-grid">
                <div class="screenshot-card">
                    <div class="phone-mockup contain">
                        <div class="phone-notch">
                            <span class="notch-speaker"></span>
                            <span class="notch-camera"></span>
                        </div>
                        <img class="phone-screen" src="{{asset('assets/images/products.png')}}"
                             alt="Teymur Store – Məhsullar">
                    </div>
                    <p class="screenshot-description">Bütün məhsullar bir nəzər altında</p>
                </div>
                <div class="screenshot-card fade-in">
                    <div class="phone-mockup contain">
                        <div class="phone-notch">
                            <span class="notch-speaker"></span>
                            <span class="notch-camera"></span>
                        </div>
                        <img class="phone-screen" src="{{asset('assets/images/categories.png')}}"
                             alt="Teymur Store – Kateqoriya">
                    </div>
                    <p class="screenshot-description">Asanlıqla kateqoriyalar arasında gəzin</p>
                </div>
                <div class="screenshot-card fade-in">
                    <div class="phone-mockup contain">
                        <div class="phone-notch">
                            <span class="notch-speaker"></span>
                            <span class="notch-camera"></span>
                        </div>
                        <img class="phone-screen" src="{{asset('assets/images/payment.png')}}"
                             alt="Teymur Store – Ödəmə">
                    </div>
                    <p class="screenshot-description">Tez və təhlükəsiz ödəniş</p>
                </div>
                <div class="screenshot-card fade-in">
                    <div class="phone-mockup contain">
                        <div class="phone-notch">
                            <span class="notch-speaker"></span>
                            <span class="notch-camera"></span>
                        </div>
                        <img class="phone-screen" src="{{asset('assets/images/order.png')}}"
                             alt="Teymur Store – Sifariş">
                    </div>
                    <p class="screenshot-description">Sifarişlərinizi izləyin</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about" aria-labelledby="about-title">
        <div class="container about-container">
            <h2 id="about-title" class="section-title">Haqqımızda</h2>
            <div class="about-content">
                <p class="about-text">
                    Teymur Store, müştərilərinə ən yaxşı onlayn alış-veriş təcrübəsini təqdim etmək məqsədilə yaradılmış
                    müasir mobil tətbiqdir. Biz keyfiyyəti və müştəri məmnuniyyətini prioritet olaraq qəbul edirik.
                </p>
                <p class="about-text">
                    Tətbiqimiz vasitəsilə minlərlə məhsul arasından seçim edə, güzəştli qiymətlərdən yararlanaraq
                    asanlıqla sifariş verə bilərsiniz. Sürətli çatdırılma xidməti və 24/7 müştəri dəstəyi ilə sizə
                    xidmət göstərməkdən qürur duyuruq.
                </p>
                <p class="about-text">
                    Missiyamız, alış-verişi daha rahat, sürətli və əlçatan etməkdir. Təhlükəsiz ödəniş sistemləri və
                    istifadəçi dostu interfeys ilə sizə ən yaxşı təcrübəni təqdim edirik. Hər gün minlərlə müştəri
                    bizimlə alış-veriş edərək öz ehtiyaclarını ödəyir.
                </p>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="faq" aria-labelledby="faq-title">
        <div class="container faq-container">
            <h2 id="faq-title" class="section-title">Tez-tez verilən suallar</h2>

            <div class="accordion" role="region" aria-label="Tez-tez verilən suallar">

                @forelse($faqs as $key=>$faq)
                    <div class="accordion-item">
                        <button class="accordion-button" aria-expanded="false" aria-controls="{{'faq'.($key+1)}}">
                            <span class="accordion-title">{{$faq['title']}}</span>
                            <svg class="accordion-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                            </svg>
                        </button>
                        <div class="accordion-content" id="{{'faq'.($key+1)}}">
                            <p>{{$faq['description']}}</p>
                        </div>
                    </div>
                @empty
                    <div class="accordion-item">
                        <button class="accordion-button" aria-expanded="false" aria-controls="faq1">
                            <span class="accordion-title">Tətbiqi necə yükləyə bilərəm?</span>
                            <svg class="accordion-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                            </svg>
                        </button>
                        <div class="accordion-content" id="faq1">
                            <p>Tətbiqi App Store və ya Google Play mağazalarından pulsuz yükləyə bilərsiniz. Sadəcə
                                mağazada
                                "Teymur Store" axtarın və yükləməyə başlayın. Quraşdırma prosesi avtomatik olaraq
                                başlayacaq
                                və bir neçə saniyə ərzində tamamlanacaq.</p>
                        </div>
                    </div>
                @endforelse

            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact" aria-labelledby="contact-title">
        <div class="container contact-container">
            <h2 id="contact-title" class="section-title">Əlaqə</h2>
            <p class="section-subtitle">Bizimlə əlaqə saxlayın</p>

            <div class="contact-wrapper">
                <div class="contact-info">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                        </div>
                        <h3 class="contact-card-title">E-poçt</h3>
                        <a href="mailto:{{$setting['email'] ?? 'info@teymurstore.az'}}" class="contact-link">{{$setting['email'] ?? 'info@teymurstore.az'}}</a>
                    </div>

                    <div class="contact-card">
                        <div class="contact-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                            </svg>
                        </div>
                        <h3 class="contact-card-title">Telefon</h3>
                        @php( $phoneNumber = $setting['phone_number_1'] ??($setting['phone_number_2'] ?? ($setting['phone_number_3'] ?? ($setting['phone_number_4'] ?? ''))))
                        <a href="{{'tel:'.$phoneNumber}}" class="contact-link">{{$phoneNumber}}</a>
                    </div>

                    <div class="contact-card">
                        <div class="contact-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                        </div>
                        <h3 class="contact-card-title">Ünvan</h3>
                        <p class="contact-text">{{$setting['address'] ?? 'Əhmədli, Seyid Əzim Şirvani 1B'}}</p>
                    </div>
                </div>

                <form class="contact-form" id="contactForm" novalidate>
                    <div class="form-group">
                        <label for="name" class="form-label">Ad</label>
                        <input type="text" id="name" name="name" class="form-input" required minlength="2"
                               aria-required="true">
                        <span class="form-error" id="nameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">E-poçt</label>
                        <input type="email" id="email" name="email" class="form-input" required aria-required="true">
                        <span class="form-error" id="emailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="message" class="form-label">Mesaj</label>
                        <textarea id="message" name="message" class="form-input form-textarea" required minlength="10"
                                  rows="5" aria-required="true"></textarea>
                        <span class="form-error" id="messageError"></span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-submit">
                        <span class="btn-text">Göndər</span>
                        <span class="btn-loading" aria-hidden="true">Göndərilir...</span>
                    </button>

                    <div class="form-message form-success" id="formSuccess" role="alert" aria-live="polite">
                        <svg viewBox="0 0 24 24" class="form-message-icon" aria-hidden="true">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                        </svg>
                        <span>Mesajınız uğurla göndərildi! Tezliklə sizinlə əlaqə saxlayacağıq.</span>
                    </div>

                    <div class="form-message form-failure" id="formFailure" role="alert" aria-live="polite">
                        <svg viewBox="0 0 24 24" class="form-message-icon" aria-hidden="true">
                            <path
                                d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                        </svg>
                        <span>Xəta baş verdi. Zəhmət olmasa bir daha cəhd edin.</span>
                    </div>
                </form>
            </div>

            <div class="social-links">
                <a href="{{$setting['instagram_url'] ?? 'https://facebook.com'}}" target="_blank" rel="noopener noreferrer" class="social-link"
                   aria-label="Facebook səhifəmiz">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/>
                    </svg>
                </a>
                <a href="{{$setting['instagram_url'] ?? 'https://instagram.com'}}" target="_blank" rel="noopener noreferrer" class="social-link"
                   aria-label="Instagram səhifəmiz">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/>
                    </svg>
                </a>
                <a href="{{$setting['tiktok_url'] ?? 'https://twitter.com'}}" target="_blank" rel="noopener noreferrer" class="social-link"
                   aria-label="Twitter səhifəmiz">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M22.46 6c-.85.38-1.78.64-2.75.76 1-.6 1.76-1.55 2.12-2.68-.93.55-1.96.96-3.06 1.18C17.93 4.38 16.92 4 15.84 4c-2.68 0-4.86 2.18-4.86 4.86 0 .38.04.75.13 1.1-4.04-.2-7.63-2.14-10.03-5.08-.42.72-.66 1.55-.66 2.44 0 1.69.86 3.18 2.17 4.05-.8-.03-1.56-.25-2.22-.61v.06c0 2.36 1.68 4.32 3.9 4.77-.41.11-.84.17-1.28.17-.31 0-.62-.03-.92-.08.63 1.95 2.44 3.37 4.6 3.41-1.68 1.32-3.8 2.1-6.1 2.1-.4 0-.79-.02-1.17-.07 2.18 1.4 4.77 2.21 7.55 2.21 9.05 0 14-7.5 14-14 0-.21 0-.42-.02-.63.96-.69 1.8-1.56 2.46-2.55z"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="footer">
    <div class="container footer-container">
        <div class="footer-logo">
            <img src="{{asset('assets/images/logo-white.png')}}" alt="Teymur Store" class="footer-logo-img">
        </div>

        <nav class="footer-nav" aria-label="Footer naviqasiya">
            <a href="#about" class="footer-link">Haqqımızda</a>
            <a href="#faq" class="footer-link">FAQ</a>
            <button id="privacyBtn" class="footer-link footer-btn">Məxfilik siyasəti</button>
            <a href="#contact" class="footer-link">Əlaqə</a>
        </nav>

        <p class="footer-copyright">
            © {{ date('Y') }} Teymur Store. Bütün hüquqlar qorunur.
        </p>
    </div>
</footer>

<!-- Privacy Policy Modal -->
<div class="modal" id="privacyModal" role="dialog" aria-labelledby="privacyTitle" aria-modal="true" hidden>
    <div class="modal-overlay" id="modalOverlay"></div>
    <div class="modal-container" role="document">
        <div class="modal-header">
            <h2 id="privacyTitle" class="modal-title">Məxfilik siyasəti</h2>
            <button class="modal-close" id="modalClose" aria-label="Bağla">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <section class="privacy-section">
                <h3 class="privacy-subtitle">1. Topladığımız məlumatlar</h3>
                <p class="privacy-text">
                    Biz sizin şəxsi məlumatlarınızı yalnız xidmətlərimizi təqdim etmək məqsədilə toplayırıq. Bu
                    məlumatlara ad, soyad, e-poçt ünvanı, telefon nömrəsi və çatdırılma ünvanı daxildir. Həmçinin tətbiq
                    istifadəsi zamanı cihaz məlumatları və IP ünvanı kimi texniki məlumatlar da toplanır.
                </p>
            </section>

            <section class="privacy-section">
                <h3 class="privacy-subtitle">2. Məlumatların istifadəsi</h3>
                <p class="privacy-text">
                    Topladığımız məlumatlar sifarişlərinizi emal etmək, sizinlə əlaqə saxlamaq, xidmət keyfiyyətini
                    yaxşılaşdırmaq və təhlükəsizliyi təmin etmək üçün istifadə olunur. Həmçinin sizə fərdiləşdirilmiş
                    təkliflər göndərmək və tətbiqimizi təkmilləşdirmək üçün analitik məlumatlardan istifadə edirik.
                </p>
            </section>

            <section class="privacy-section">
                <h3 class="privacy-subtitle">3. Məlumatların təhlükəsizliyi</h3>
                <p class="privacy-text">
                    Biz sizin şəxsi məlumatlarınızın təhlükəsizliyini təmin etmək üçün ən müasir texnologiyalardan
                    istifadə edirik. Məlumatlarınız SSL şifrələməsi ilə qorunur və təhlükəsiz serverlərdə saxlanılır.
                    Ödəniş məlumatları heç vaxt bizim serverlərə saxlanılmır və birbaşa ödəniş provayderlərə ötürülür.
                </p>
            </section>

            <section class="privacy-section">
                <h3 class="privacy-subtitle">4. Üçüncü tərəflərlə paylaşma</h3>
                <p class="privacy-text">
                    Sizin icazəniz olmadan şəxsi məlumatlarınızı üçüncü tərəflərlə paylaşmırıq. İstisna hallar yalnız
                    qanuni tələblərə cavab vermək, çatdırılma xidmətlərini təmin etmək və ödəniş proseslərini həyata
                    keçirmək üçün ola bilər. Bu hallarda məlumatlarınız yalnız zəruri məqsədlər üçün paylaşılır.
                </p>
            </section>

            <section class="privacy-section">
                <h3 class="privacy-subtitle">5. Saxlanma müddəti</h3>
                <p class="privacy-text">
                    Şəxsi məlumatlarınız yalnız xidmətlərimizi təqdim etmək üçün lazım olan müddət ərzində saxlanılır.
                    Hesabınızı bağladıqdan sonra məlumatlarınız 90 gün ərzində silinir. Qanuni öhdəliklərimizi yerinə
                    yetirmək üçün bəzi məlumatlar daha uzun müddət saxlanıla bilər.
                </p>
            </section>

            <section class="privacy-section">
                <h3 class="privacy-subtitle">6. Hüquqlarınız</h3>
                <p class="privacy-text">
                    Siz öz şəxsi məlumatlarınıza daxil ola, onları düzəldə, silə və ya emal edilməsini məhdudlaşdıra
                    bilərsiniz. Həmçinin məlumatlarınızın portativliyi hüququna maliksiniz. Bu hüquqlardan istifadə
                    etmək üçün bizimlə əlaqə saxlaya bilərsiniz.
                </p>
            </section>

            <section class="privacy-section">
                <h3 class="privacy-subtitle">7. Cookie-lər və izləmə texnologiyaları</h3>
                <p class="privacy-text">
                    Saytımız və tətbiqimiz istifadəçi təcrübəsini yaxşılaşdırmaq üçün cookie-lərdən və oxşar
                    texnologiyalardan istifadə edir. Brauzer və ya tətbiq parametrlərinizdən cookie-ləri idarə edə
                    bilərsiniz. Lakin bu, bəzi funksiyaların düzgün işləməməsinə səbəb ola bilər.
                </p>
            </section>

            <section class="privacy-section">
                <h3 class="privacy-subtitle">8. Dəyişikliklər</h3>
                <p class="privacy-text">
                    Məxfilik siyasətimiz vaxtaşırı yenilənə bilər. Əhəmiyyətli dəyişikliklər haqqında sizə e-poçt və ya
                    tətbiqdaxili bildirişlər vasitəsilə məlumat verəcəyik. Dəyişikliklərin qüvvəyə minmə tarixi
                    siyasətimizin yuxarı hissəsində göstəriləcək.
                </p>
            </section>

            <section class="privacy-section">
                <h3 class="privacy-subtitle">9. Əlaqə</h3>
                <p class="privacy-text">
                    Məxfilik siyasətimizlə bağlı suallarınız varsa və ya hüquqlarınızdan istifadə etmək istəyirsinizsə,
                    bizə info@teymurstore.az ünvanından yazın. Biz
                    48 saat ərzində sizə cavab verəcəyik.
                </p>
            </section>
        </div>
    </div>
</div>

<script src="{{asset('assets/app.js')}}" defer></script>
</body>
</html>
