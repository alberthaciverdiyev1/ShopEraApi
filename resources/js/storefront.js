/**
 * Storefront interactions (Blade pages).
 * Vanilla JS on purpose: Blade pages are rendered server-side, so no framework.
 */

const catalogToggle = document.querySelector('[data-sf-catalog-toggle]');
const catalogMenu = document.querySelector('[data-sf-catalog-menu]');
const menuToggle = document.querySelector('[data-sf-menu-toggle]');
const mobileMenu = document.querySelector('[data-sf-mobile-menu]');

function closeCatalog() {
    if (!catalogMenu || !catalogToggle) return;

    catalogMenu.hidden = true;
    catalogToggle.setAttribute('aria-expanded', 'false');
}

if (catalogToggle && catalogMenu) {
    catalogToggle.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = catalogMenu.hidden;

        catalogMenu.hidden = !willOpen;
        catalogToggle.setAttribute('aria-expanded', String(willOpen));
    });

    document.addEventListener('click', (event) => {
        if (!catalogMenu.contains(event.target) && !catalogToggle.contains(event.target)) {
            closeCatalog();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeCatalog();
        }
    });
}

if (menuToggle && mobileMenu) {
    menuToggle.addEventListener('click', () => {
        const willOpen = mobileMenu.hidden;

        mobileMenu.hidden = !willOpen;
        menuToggle.setAttribute('aria-expanded', String(willOpen));
        menuToggle.classList.toggle('is-open', willOpen);
    });
}

/* Live support widget: launcher, teaser and window are always rendered. */
const chat = document.querySelector('[data-sf-chat]');

if (chat) {
    const chatWindow = chat.querySelector('.sf-chat__window');
    const backdrop = chat.querySelector('.sf-chat__backdrop');
    const teaser = chat.querySelector('[data-sf-chat-teaser]');
    const launcher = chat.querySelector('.sf-chat__launcher');
    const iconOpen = chat.querySelector('[data-sf-chat-icon-open]');
    const iconClose = chat.querySelector('[data-sf-chat-icon-close]');

    const setChatOpen = (open) => {
        chatWindow.hidden = !open;
        backdrop.hidden = !open;
        launcher.classList.toggle('is-open', open);
        launcher.setAttribute('aria-expanded', String(open));
        iconOpen.hidden = open;
        iconClose.hidden = !open;

        if (open) {
            teaser.hidden = true;
        }
    };

    chat.querySelectorAll('[data-sf-chat-toggle]').forEach((element) => {
        element.addEventListener('click', () => setChatOpen(chatWindow.hidden));
    });

    chat.querySelectorAll('[data-sf-chat-close]').forEach((element) => {
        element.addEventListener('click', () => setChatOpen(false));
    });

    const teaserClose = chat.querySelector('[data-sf-chat-teaser-close]');

    if (teaserClose) {
        teaserClose.addEventListener('click', (event) => {
            event.stopPropagation();
            teaser.hidden = true;
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !chatWindow.hidden) {
            setChatOpen(false);
        }
    });
}

/* Popup modal: shown once per session, 700ms after load. */
const popup = document.querySelector('[data-sf-popup]');
const POPUP_STORAGE_KEY = 'popup:dismissed';
const POPUP_DELAY = 700;

function closePopup() {
    if (!popup) return;

    popup.hidden = true;

    try {
        sessionStorage.setItem(POPUP_STORAGE_KEY, '1');
    } catch (error) {
        /* storage can be unavailable; the popup just shows again next page */
    }
}

if (popup) {
    let dismissed = false;

    try {
        dismissed = sessionStorage.getItem(POPUP_STORAGE_KEY) === '1';
    } catch (error) {
        dismissed = false;
    }

    if (dismissed) {
        popup.remove();
    } else {
        window.setTimeout(() => {
            popup.hidden = false;
        }, POPUP_DELAY);

        popup.querySelectorAll('[data-sf-popup-close]').forEach((element) => {
            element.addEventListener('click', closePopup);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !popup.hidden) {
                closePopup();
            }
        });
    }
}
