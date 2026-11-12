@props([])

{{--
    Live support widget. There is no feature gating: the storefront has a
    single feature set, so every visitor gets the launcher. The chat stream
    itself needs a signed-in user; until the Blade auth migration lands the
    window shows the guest card with login/register links.
--}}

<div class="sf-chat" data-sf-chat>
    <div class="sf-chat__teaser" data-sf-chat-teaser>
        <button type="button" class="sf-chat__teaser-close" data-sf-chat-teaser-close aria-label="Bağla">
            &times;
        </button>
        <button type="button" class="sf-chat__teaser-text" data-sf-chat-toggle>
            <span class="sf-chat__teaser-title">Canlı dəstək</span>
            <span class="sf-chat__teaser-desc">Sualınızı yazın, canlı söhbətə başlayaq!</span>
        </button>
    </div>

    <button
        type="button"
        class="sf-chat__launcher"
        data-sf-chat-toggle
        aria-expanded="false"
        aria-controls="sf-chat-window"
        aria-label="Canlı dəstək"
        title="Canlı dəstək"
    >
        <i class="fa-solid fa-headset" data-sf-chat-icon-open></i>
        <i class="fa-solid fa-xmark" data-sf-chat-icon-close hidden></i>
    </button>

    <div class="sf-chat__backdrop" data-sf-chat-close hidden></div>

    <div class="sf-chat__window" id="sf-chat-window" role="dialog" aria-modal="true" aria-label="Canlı dəstək" hidden>
        <div class="sf-chat__header">
            <div class="sf-chat__header-info">
                <div class="sf-chat__avatar">
                    <i class="fa-solid fa-headset"></i>
                    <span class="sf-chat__online-dot"></span>
                </div>
                <div class="sf-chat__meta">
                    <h4>Canlı dəstək</h4>
                    <p><span class="sf-chat__status-dot"></span> Onlayn • Adətən bir neçə dəqiqə içində cavab veririk</p>
                </div>
            </div>

            <button type="button" class="sf-chat__header-btn" data-sf-chat-close aria-label="Bağla" title="Bağla">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sf-chat__guest">
            <div class="sf-chat__guest-icon">
                <i class="fa-solid fa-comments"></i>
            </div>
            <h4>Canlı dəstək</h4>
            <p>Dəstək komandası ilə söhbət etmək üçün daxil olun.</p>

            <div class="sf-chat__guest-actions">
                <a href="/login" class="sf-chat__btn sf-chat__btn--primary">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span>Giriş</span>
                </a>
                <a href="/register" class="sf-chat__btn sf-chat__btn--secondary">Qeydiyyat</a>
            </div>

            <div class="sf-chat__guest-note">
                <i class="fa-solid fa-shield"></i>
                <span>7/24 Müştəri Xidmətləri</span>
            </div>
        </div>
    </div>
</div>
