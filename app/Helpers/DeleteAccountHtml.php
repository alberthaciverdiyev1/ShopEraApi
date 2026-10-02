<?php

namespace App\Helpers;

class DeleteAccountHtml
{
    public function __invoke()
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>ShopEra – Hesabın silinməsi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ShopEra hesabınızın və şəxsi məlumatlarınızın silinməsi üçün müraciət formu.">
    <style>
        :root {
            --bg: #020617;
            --bg-alt: #0b1120;
            --primary: #f97316;
            --primary-soft: rgba(249, 115, 22, 0.15);
            --text: #e5e7eb;
            --muted: #9ca3af;
            --danger: #fca5a5;
            --border: rgba(148, 163, 184, 0.35);
            --radius-lg: 18px;
            --radius-xl: 24px;
            --shadow-soft: 0 18px 60px rgba(15, 23, 42, 0.75);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(249,115,22,0.20), transparent 55%),
                radial-gradient(circle at bottom right, rgba(56,189,248,0.18), transparent 55%),
                var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .shell { width: 100%; max-width: 480px; }

        .card {
            background: linear-gradient(145deg, rgba(15,23,42,0.98), rgba(15,23,42,0.92));
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-soft);
            border: 1px solid var(--border);
            padding: 24px 22px 22px;
            backdrop-filter: blur(18px);
        }

        @media (min-width: 640px) {
            .card { padding: 28px 28px 26px; }
        }

        .logo-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .logo-row img { height: 28px; }

        .tag {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            padding: 4px 9px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            border: 1px solid rgba(249, 115, 22, 0.35);
        }

        h1 {
            font-size: 22px;
            margin: 0 0 6px;
            letter-spacing: -0.02em;
        }

        .subtitle {
            margin: 0 0 20px;
            font-size: 14px;
            line-height: 1.5;
            color: var(--muted);
        }

        .info-box {
            font-size: 12px;
            line-height: 1.5;
            color: var(--muted);
            padding: 10px 12px;
            border-radius: var(--radius-lg);
            border: 1px dashed rgba(148,163,184,0.6);
            background: rgba(15,23,42,0.85);
            margin-bottom: 20px;
        }

        .info-box strong { color: var(--text); }

        form {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        label {
            font-size: 13px;
            margin-bottom: 4px;
            color: #e5e7eb;
        }

        .required-dot { color: var(--primary); margin-left: 2px; }

        input,
        textarea {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(15,23,42,0.9);
            padding: 11px;
            color: var(--text);
            font-size: 14px;
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }

        textarea { resize: vertical; min-height: 80px; max-height: 180px; }

        input::placeholder,
        textarea::placeholder { color: #6b7280; }

        input:focus,
        textarea:focus {
            border-color: var(--primary);
            background: rgba(15,23,42,0.95);
            box-shadow: 0 0 0 1px rgba(249,115,22,0.35);
        }

        .field-error {
            margin-top: 4px;
            font-size: 11px;
            color: var(--danger);
        }

        .checkbox-row {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .checkbox-row input[type="checkbox"] {
            margin-top: 3px;
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1px solid var(--border);
            accent-color: var(--primary);
        }

        .checkbox-label {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.5;
        }

        .actions { margin-top: 6px; display: flex; flex-direction: column; gap: 8px; }

        button {
            border: none;
            cursor: pointer;
            border-radius: 999px;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 600;
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: #0b1120;
            box-shadow: 0 16px 40px rgba(249,115,22,0.35);
            transition: transform .12s, box-shadow .12s, filter .12s;
        }

        button:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
            box-shadow: 0 20px 55px rgba(249,115,22,0.4);
        }

        button:active {
            transform: translateY(0);
            box-shadow: 0 10px 30px rgba(249,115,22,0.3);
        }

        .button-secondary {
            background: transparent;
            color: var(--muted);
            box-shadow: none;
            padding: 0;
            justify-content: flex-start;
        }

        .button-secondary:hover { color: var(--text); }

        .button-secondary span { font-size: 12px; }

        .hint {
            font-size: 11px;
            color: var(--muted);
        }

        .footer-note {
            margin-top: 10px;
            font-size: 11px;
            color: #6b7280;
        }

        .footer-note a {
            color: var(--primary);
            text-decoration: none;
        }

        .footer-note a:hover { text-decoration: underline; }

        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.88);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 50;
        }

        .overlay.visible { display: flex; }

        .overlay-card {
            background: var(--bg-alt);
            border-radius: 20px;
            border: 1px solid var(--border);
            padding: 22px 20px 18px;
            max-width: 360px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-soft);
        }

        .check-icon-wrap {
            width: 46px;
            height: 46px;
            border-radius: 999px;
            margin: 0 auto 10px;
            background: var(--primary-soft);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .check-icon {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            border: 2px solid var(--primary);
            position: relative;
        }

        .check-icon::after {
            content: "";
            position: absolute;
            left: 6px;
            top: 4px;
            width: 8px;
            height: 14px;
            border-right: 2px solid var(--primary);
            border-bottom: 2px solid var(--primary);
            transform: rotate(40deg);
        }

        @media (max-width: 360px) {
            .card { padding: 22px 18px 18px; }
            h1 { font-size: 20px; }
        }
    </style>
</head>

<body>
<div class="shell">
    <div class="card" role="form" aria-labelledby="title">
        <div class="logo-row">
            <img src="https://shopera.az/assets/images/logo-white.png" alt="ShopEra Logo">
            <span class="tag">Hesabın silinməsi</span>
        </div>

        <h1 id="title">ShopEra hesabının silinməsi</h1>
        <p class="subtitle">
            Buradan ShopEra hesabınızın və şəxsi məlumatlarınızın silinməsi üçün müraciət göndərə bilərsiniz.
        </p>

        <div class="info-box">
            <strong>Qeyd:</strong>
            Hesab silinməsi icazə hüququnuzdur. Müraciətiniz təsdiqləndikdən sonra hesab məlumatlarınız qanuni tələblərə uyğun şəkildə sistemimizdən silinəcək və ya anonimləşdiriləcək.
        </div>

        <form id="deleteForm" novalidate>
            <div>
                <label for="email">
                    E-poçt ünvanınız <span class="required-dot">*</span>
                </label>
                <input type="email" id="email" name="email" inputmode="email" autocomplete="email" placeholder="[email protected]" required>
                <div class="field-error" id="emailError"></div>
            </div>

            <div>
                <label for="message">Əlavə qeyd (istəyə görə)</label>
                <textarea id="message" name="message" placeholder="Məsələn: Hesabımdakı bütün sifariş tarixçəsinin və saxlanmış ünvanların silinməsini istəyirəm."></textarea>
            </div>

            <div>
                <div class="checkbox-row">
                    <input type="checkbox" id="confirmDelete" name="confirmDelete">
                    <label for="confirmDelete" class="checkbox-label">
                        Hesabımın və ona bağlı şəxsi məlumatlarımın silinməsi ilə bağlı müraciət göndərdiyimi təsdiq edirəm.
                    </label>
                </div>
                <div class="field-error" id="confirmError"></div>
            </div>

            <div class="actions">
                <button type="submit">
                    Silinmə istəyi göndər <span aria-hidden="true">↗</span>
                </button>

                <button type="button" class="button-secondary" onclick="window.location.href='https://shopera.az';">
                    <span>Ana səhifəyə qayıt</span>
                </button>

                <div class="hint">
                    Formu göndərdikdən sonra komandamız qısa müddət ərzində sizinlə e-poçt vasitəsilə əlaqə saxlayacaq.
                </div>
            </div>
        </form>

        <p class="footer-note">
            Məxfilik siyasətimiz haqqında daha ətraflı:
            <a href="https://shopera.az/#məxfilik-siyasəti" target="_blank" rel="noopener noreferrer">
                ShopEra Məxfilik siyasəti
            </a>.
        </p>
    </div>
</div>

<div class="overlay" id="successOverlay" aria-modal="true" role="dialog" aria-labelledby="successTitle">
    <div class="overlay-card">
        <div class="check-icon-wrap">
            <div class="check-icon"></div>
        </div>
        <h2 id="successTitle">Silinmə istəyi göndərildi</h2>
        <p class="overlay-text">
            Təşəkkürlər! Hesabın silinməsi üçün müraciətiniz qəbul olundu. Komandamız ən qısa zamanda sizinlə e-poçt vasitəsilə əlaqə saxlayacaq.
        </p>
        <button type="button" id="closeOverlayBtn">Bağla</button>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('deleteForm');
    const emailInput = document.getElementById('email');
    const confirmCheckbox = document.getElementById('confirmDelete');

    const emailError = document.getElementById('emailError');
    const confirmError = document.getElementById('confirmError');

    const overlay = document.getElementById('successOverlay');
    const closeOverlayBtn = document.getElementById('closeOverlayBtn');

    function validateEmail(value) {
        if (!value) return 'E-poçt ünvanı tələb olunur.';
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regex.test(value)) return 'Zəhmət olmasa düzgün e-poçt ünvanı daxil edin.';
        return '';
    }

    function clearErrors() {
        emailError.textContent = '';
        confirmError.textContent = '';
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        clearErrors();

        const email = emailInput.value.trim();
        const message = document.getElementById('message').value.trim();
        const confirmChecked = confirmCheckbox.checked;

        let hasError = false;

        const emailValidation = validateEmail(email);
        if (emailValidation) {
            emailError.textContent = emailValidation;
            hasError = true;
        }

        if (!confirmChecked) {
            confirmError.textContent = 'Hesab silinməsi ilə bağlı razılığınızı təsdiq etməlisiniz.';
            hasError = true;
        }

        if (hasError) return;

        console.log('Account delete request:', { email, message });

        overlay.classList.add('visible');
        form.reset();
    });

    closeOverlayBtn.addEventListener('click', function () {
        overlay.classList.remove('visible');
    });

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.classList.remove('visible');
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('visible')) {
            overlay.classList.remove('visible');
        }
    });
})();
</script>
</body>
</html>
HTML;
    }
}
