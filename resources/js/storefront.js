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

const faq = document.querySelector('[data-sf-faq]');

if (faq) {
    faq.querySelectorAll('[data-sf-faq-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const item = toggle.closest('.sf-faq__item');
            const answer = item?.querySelector('.sf-faq__answer');
            if (!item || !answer) return;

            const isOpen = item.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
            answer.hidden = !isOpen;
        });
    });
}

/* Header: sticky, catalog mega menu and search suggestions */
(() => {
    const header = document.getElementById('header-sticky');

    if (header) {
        const onScroll = () => header.classList.toggle('sticky', window.scrollY > 250);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    const catalog = document.querySelector('[data-sf-catalog]');
    const catalogToggle = document.querySelector('[data-sf-catalog-toggle]');

    if (catalog && catalogToggle) {
        const panel = catalog.querySelector('[data-sf-catalog-menu]');
        const parentLinks = panel ? Array.from(panel.querySelectorAll('[data-catalog-parent]')) : [];

        const setPanelColumns = (count) => {
            if (!panel) return;
            panel.classList.remove('columns-1', 'columns-2', 'columns-3');
            panel.classList.add(`columns-${Math.min(count, 3)}`);
        };

        const activateParent = (id) => {
            parentLinks.forEach((link) => link.classList.toggle('active', link.dataset.catalogParent === String(id)));

            panel?.querySelectorAll('[data-catalog-children]').forEach((column) => {
                column.hidden = column.dataset.catalogChildren !== String(id);
            });
            panel?.querySelectorAll('[data-catalog-leaves]').forEach((column) => {
                column.hidden = true;
            });

            const childColumn = panel?.querySelector(`[data-catalog-children="${id}"]`);
            setPanelColumns(childColumn ? (childColumn.querySelector('[data-catalog-child]') ? 2 : 1) : 1);
        };

        parentLinks.forEach((link) => {
            const id = link.dataset.catalogParent;
            link.addEventListener('mouseenter', () => activateParent(id));
            link.addEventListener('focus', () => activateParent(id));
        });

        panel?.querySelectorAll('[data-catalog-child]').forEach((link) => {
            const id = link.dataset.catalogChild;

            const activateChild = () => {
                panel.querySelectorAll('[data-catalog-child]').forEach((item) => {
                    item.classList.toggle('active', item.dataset.catalogChild === id);
                });
                panel.querySelectorAll('[data-catalog-leaves]').forEach((column) => {
                    column.hidden = column.dataset.catalogLeaves !== id;
                });

                const leaves = panel.querySelector(`[data-catalog-leaves="${id}"]`);
                const parent = link.closest('[data-catalog-children]');
                const parentId = parent?.dataset.catalogChildren;

                parentLinks.forEach((item) => {
                    item.classList.toggle('active', item.dataset.catalogParent === String(parentId));
                });
                panel.querySelectorAll('[data-catalog-children]').forEach((column) => {
                    column.hidden = column.dataset.catalogChildren !== parentId;
                });

                setPanelColumns(leaves ? 3 : 2);
            };

            link.addEventListener('mouseenter', activateChild);
            link.addEventListener('focus', activateChild);
        });

        catalogToggle.addEventListener('click', (event) => {
            event.preventDefault();
            const open = catalog.classList.toggle('catalog-open');
            catalogToggle.setAttribute('aria-expanded', String(open));
            if (open && parentLinks[0]) activateParent(parentLinks[0].dataset.catalogParent);
        });

        document.addEventListener('click', (event) => {
            if (!catalog.contains(event.target)) {
                catalog.classList.remove('catalog-open');
                catalogToggle.setAttribute('aria-expanded', 'false');
            }
        });

        if (parentLinks[0]) activateParent(parentLinks[0].dataset.catalogParent);
    }

    const search = document.querySelector('[data-sf-search]');

    if (search) {
        const input = search.querySelector('[data-sf-search-input]');
        const results = search.querySelector('[data-sf-search-results]');
        const spinner = search.querySelector('[data-sf-search-spinner]');
        const clear = search.querySelector('[data-sf-search-clear]');
        const fallback = '/assets/images/top-deals-item/topDealsItemThumb1_1.png';
        let timer;

        const escape = (value) => String(value).replace(/[&<>"']/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        })[character]);

        const close = () => {
            if (results) results.hidden = true;
            if (results) results.innerHTML = '';
            if (clear) clear.hidden = true;
        };

        const show = (html) => {
            if (!results) return;
            results.innerHTML = html;
            results.hidden = false;
        };

        const render = (items, query) => {
            if (!items.length) {
                show('<div class="search-status-box"><i class="fa-regular fa-face-frown text-muted me-2"></i><span>Uyğun məhsul tapılmadı</span></div>');
                return;
            }

            const rows = items.map((item) => {
                const image = item.images?.[0]?.image_path || fallback;
                const title = escape(item.title ?? '');
                const category = item.category?.name ? escape(item.category.name) : '';
                const price = Number(item.discount || item.price || 0).toFixed(2);
                const url = item.slug ? `/elan/${encodeURIComponent(item.slug)}` : `/shop/details?id=${item.id}`;

                return `<a href="${url}" class="result-item">
                    <img src="${escape(image)}" alt="${title}" class="result-thumb" loading="lazy" onerror="this.onerror=null;this.src='${fallback}'">
                    <div class="result-info">
                        <div class="result-title">${title}</div>
                        <div class="result-meta">
                            ${category ? `<span class="result-category">${category}</span>` : ''}
                            <span class="result-price">${price} ₼</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right result-arrow"></i>
                </a>`;
            }).join('');

            show(`<div class="dropdown-header"><span>Məhsullar</span></div>
                <div class="results-list">${rows}</div>
                <div class="dropdown-footer">
                    <a class="btn-view-all" href="/shop?search=${encodeURIComponent(query)}">Hamısına bax <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>`);
        };

        const run = async (query) => {
            if (spinner) spinner.hidden = false;

            try {
                const response = await fetch(`/api/product?search=${encodeURIComponent(query)}&per_page=5&is_application=1`, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) throw new Error('search failed');

                const payload = await response.json();
                const items = Array.isArray(payload) ? payload : (payload.data ?? []);
                render(items, query);
            } catch (error) {
                show('<div class="search-status-box">Axtarış alınmadı</div>');
            } finally {
                if (spinner) spinner.hidden = true;
            }
        };

        input?.addEventListener('input', () => {
            const query = input.value.trim();
            clearTimeout(timer);

            if (clear) clear.hidden = query.length === 0;

            if (query.length < 2) {
                close();
                return;
            }

            timer = setTimeout(() => run(query), 250);
        });

        clear?.addEventListener('click', () => {
            if (input) {
                input.value = '';
                input.focus();
            }
            close();
        });

        document.addEventListener('click', (event) => {
            if (!search.contains(event.target)) close();
        });
    }

    /* Password visibility toggles (login/register) */
    document.querySelectorAll('[data-sf-password-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const input = toggle.closest('.sf-auth-password')?.querySelector('[data-sf-password]');
            if (!input) return;

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-label', show ? 'Şifrəni gizlət' : 'Şifrəni göstər');
            toggle.innerHTML = `<i class="fa-regular ${show ? 'fa-eye-slash' : 'fa-eye'}"></i>`;
        });
    });

    /* Mobile bottom navigation + account drawer */
    const bottomNav = document.querySelector('[data-sf-bottom-nav]');
    const drawer = document.getElementById('mobile-account-drawer');

    if (bottomNav) {
        let lastScrollY = window.scrollY;
        let ticking = false;

        window.addEventListener('scroll', () => {
            if (ticking) return;

            window.requestAnimationFrame(() => {
                const currentScrollY = window.scrollY;
                const diff = currentScrollY - lastScrollY;

                if (Math.abs(diff) > 8) {
                    if (currentScrollY > 80 && diff > 0) {
                        bottomNav.classList.add('nav-hidden');
                    } else if (diff < 0 || currentScrollY <= 80) {
                        bottomNav.classList.remove('nav-hidden');
                    }
                    lastScrollY = currentScrollY;
                }

                ticking = false;
            });
            ticking = true;
        }, { passive: true });
    }

    if (bottomNav && drawer) {
        const openButton = bottomNav.querySelector('[data-sf-account-drawer-open]');
        const closeButtons = drawer.querySelectorAll('[data-sf-account-drawer-close]');

        const openDrawer = () => {
            drawer.hidden = false;
            document.body.style.overflow = 'hidden';
            openButton?.setAttribute('aria-expanded', 'true');
        };

        const closeDrawer = () => {
            drawer.hidden = true;
            document.body.style.overflow = '';
            openButton?.setAttribute('aria-expanded', 'false');
        };

        openButton?.addEventListener('click', openDrawer);
        closeButtons.forEach((button) => button.addEventListener('click', closeDrawer));

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeDrawer();
        });
    }
})();

/* Account dashboard: tab switching (the original used Bootstrap pills). */
document.querySelectorAll('[data-sf-dash-tab]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
        const key = trigger.dataset.sfDashTab;
        const pane = document.querySelector(`[data-sf-dash-pane="${key}"]`);
        if (!pane) return;

        event.preventDefault();

        document.querySelectorAll('[data-sf-dash-pane]').forEach((item) => {
            item.classList.toggle('is-active', item === pane);
        });

        document.querySelectorAll('.dashboard-navigation-sidebar [data-sf-dash-tab]').forEach((button) => {
            const active = button.dataset.sfDashTab === key;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        history.replaceState(null, '', key === 'dashboard' ? location.pathname : `?tab=${key}`);
    });
});
