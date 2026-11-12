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

/* Shop filters: selects and radios submit the GET form immediately. */
document.querySelectorAll('[data-sf-auto-submit]').forEach((input) => {
    input.addEventListener('change', () => {
        const form = input.closest('form');

        if (!form) return;

        const page = form.querySelector('input[name="page"]');

        if (page) page.value = '1';

        form.submit();
    });
});

/* Mobile filter drawer. */
const sidebar = document.querySelector('[data-sf-sidebar]');
const sidebarToggle = document.querySelector('[data-sf-mobile-filters]');
const sidebarBackdrop = document.querySelector('.sf-shop__backdrop');

function setSidebarOpen(open) {
    if (!sidebar) return;

    sidebar.classList.toggle('is-open', open);
    document.body.classList.toggle('sf-no-scroll', open);

    if (sidebarBackdrop) sidebarBackdrop.hidden = !open;
}

if (sidebar && sidebarToggle) {
    sidebarToggle.addEventListener('click', () => setSidebarOpen(!sidebar.classList.contains('is-open')));
}

document.querySelectorAll('[data-sf-sidebar-close]').forEach((element) => {
    element.addEventListener('click', () => setSidebarOpen(false));
});

/* Product gallery: thumbnails, arrows and a lightbox. */
const gallery = document.querySelector('[data-sf-gallery]');

if (gallery) {
    const image = gallery.querySelector('[data-sf-gallery-image]');
    const thumbs = Array.from(gallery.querySelectorAll('[data-sf-gallery-thumb]'));
    const lightbox = gallery.querySelector('[data-sf-lightbox]');
    const lightboxImage = gallery.querySelector('[data-sf-lightbox-image]');
    let current = 0;

    const show = (index) => {
        if (thumbs.length === 0) return;

        current = (index + thumbs.length) % thumbs.length;
        const src = thumbs[current].dataset.src;

        if (image) image.src = src;

        thumbs.forEach((thumb, position) => thumb.classList.toggle('is-active', position === current));
    };

    thumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', () => show(index));
    });

    gallery.querySelector('[data-sf-gallery-prev]')?.addEventListener('click', () => show(current - 1));
    gallery.querySelector('[data-sf-gallery-next]')?.addEventListener('click', () => show(current + 1));

    if (lightbox && lightboxImage) {
        gallery.querySelector('[data-sf-gallery-open]')?.addEventListener('click', () => {
            lightboxImage.src = image ? image.src : lightboxImage.src;
            lightbox.hidden = false;
            document.body.classList.add('sf-no-scroll');
        });

        const closeLightbox = () => {
            lightbox.hidden = true;
            document.body.classList.remove('sf-no-scroll');
        };

        gallery.querySelector('[data-sf-lightbox-close]')?.addEventListener('click', closeLightbox);
        lightbox.addEventListener('click', (event) => {
            if (event.target === lightbox) closeLightbox();
        });

        document.addEventListener('keydown', (event) => {
            if (lightbox.hidden) return;

            if (event.key === 'Escape') closeLightbox();
            if (event.key === 'ArrowLeft') {
                show(current - 1);
                lightboxImage.src = image ? image.src : lightboxImage.src;
            }
            if (event.key === 'ArrowRight') {
                show(current + 1);
                lightboxImage.src = image ? image.src : lightboxImage.src;
            }
        });
    }
}

/* Product tabs. */
document.querySelectorAll('[data-sf-tabs]').forEach((tabs) => {
    const buttons = Array.from(tabs.querySelectorAll('[data-sf-tab]'));
    const panels = Array.from(tabs.querySelectorAll('[data-sf-panel]'));

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const name = button.dataset.sfTab;

            buttons.forEach((item) => item.classList.toggle('is-active', item === button));
            panels.forEach((panel) => {
                const active = panel.dataset.sfPanel === name;

                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        });
    });
});

/* Seller contact: fetched on demand so the number never sits in the HTML. */
const contact = document.querySelector('[data-sf-contact]');

if (contact) {
    const button = contact.querySelector('[data-sf-contact-reveal]');
    const details = contact.querySelector('[data-sf-contact-details]');
    const error = contact.querySelector('[data-sf-contact-error]');

    button?.addEventListener('click', async () => {
        button.disabled = true;
        if (error) error.hidden = true;

        try {
            const response = await fetch(`/shop/listings/${contact.dataset.listing}/contact`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) throw new Error('request failed');

            const payload = await response.json();
            const data = payload.data ?? payload;
            const lines = [];
            const escape = (value) => String(value).replace(/[&<>"']/g, (character) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            })[character]);

            if (data.name) lines.push(`<strong>${escape(data.name)}</strong>`);
            if (data.phone) lines.push(`<a href="tel:${escape(data.phone)}">${escape(data.phone)}</a>`);
            if (data.email) lines.push(`<a href="mailto:${escape(data.email)}">${escape(data.email)}</a>`);

            if (lines.length === 0) throw new Error('empty contact');

            details.innerHTML = lines.join('');
            details.hidden = false;
            button.hidden = true;
        } catch (failure) {
            button.disabled = false;
            if (error) error.hidden = false;
        }
    });
}
