@props(['product'])

@auth
    {{--
        Buyer <-> seller chat for this listing. Rendered only for signed-in
        users; the modal lists the user's other conversations too. The plain
        JSON endpoints in the auth route group feed it (see storefront.js).
    --}}
    <div
        class="sf-lchat"
        data-sf-lchat
        hidden
        data-start-url="{{ route('storefront.chat.start', $product['slug']) }}"
        data-index-url="{{ route('storefront.chat.index') }}"
        data-thread-url="{{ route('storefront.chat.show', ['id' => '__ID__']) }}"
        data-send-url="{{ route('storefront.chat.send', ['id' => '__ID__']) }}"
    >
        <div class="sf-lchat__backdrop" data-sf-lchat-close></div>

        <div class="sf-lchat__panel" role="dialog" aria-modal="true" aria-label="Mesajlar">
            <header class="sf-lchat__header">
                <span class="sf-lchat__title"><i class="fa-solid fa-comments"></i> Mesajlar</span>
                <button type="button" class="sf-lchat__close" data-sf-lchat-close aria-label="Bağla">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </header>

            <div class="sf-lchat__body">
                <aside class="sf-lchat__list" data-sf-lchat-list>
                    <p class="sf-lchat__empty">Söhbətlər yüklənir…</p>
                </aside>

                <section class="sf-lchat__thread">
                    <div class="sf-lchat__thread-head" data-sf-lchat-thread-head></div>
                    <div class="sf-lchat__messages" data-sf-lchat-messages>
                        <p class="sf-lchat__empty">Söhbət seçin.</p>
                    </div>

                    <form class="sf-lchat__form" data-sf-lchat-form hidden>
                        <textarea name="body" rows="2" maxlength="2000" placeholder="Mesajınızı yazın…" required></textarea>
                        <button type="submit" class="sf-btn sf-btn--primary">Göndər</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endauth
