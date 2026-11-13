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

const MOBILE_SHOP_QUERY = '(max-width: 991.98px)';

/* Keep the mobile shop filter bar below the sticky site header. */
const siteHeader = document.querySelector('#header-sticky') ?? document.querySelector('.header-section-1');

if (siteHeader) {
    const syncHeaderHeight = () => {
        document.documentElement.style.setProperty('--sf-site-header-height', `${siteHeader.offsetHeight}px`);
    };

    syncHeaderHeight();
    window.addEventListener('resize', syncHeaderHeight);

    if ('ResizeObserver' in window) {
        new ResizeObserver(syncHeaderHeight).observe(siteHeader);
    }
}

/* Shop filters: selects and radios submit the GET form immediately, except in
   the mobile drawer, where the footer button applies all choices at once.
   Delegated so options loaded later (see "show more") work too. */
document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-sf-auto-submit]');

    if (!input) return;

    const form = input.closest('form');

    if (!form) return;

    if (window.matchMedia(MOBILE_SHOP_QUERY).matches && input.closest('.sf-shop__sidebar')) {
        return;
    }

    const page = form.querySelector('input[name="page"]');

    if (page) page.value = '1';

    form.submit();
});

/* Mobile quick filters: one select per facet, navigation keeps the other params. */
document.querySelectorAll('[data-sf-quick]').forEach((select) => {
    select.addEventListener('change', () => {
        const params = new URLSearchParams(window.location.search);
        const type = select.dataset.sfQuick;

        params.delete('page');

        if (type === 'sort') {
            params.set('sort', select.value);
        } else if (type === 'filters') {
            const filterId = select.dataset.sfFilter;

            [...params.keys()]
                .filter((key) => key.startsWith(`filters[${filterId}]`))
                .forEach((key) => params.delete(key));

            if (select.value) params.append(`filters[${filterId}][]`, select.value);
        } else {
            params.delete(type);
            params.delete(type.slice(0, -1));

            if (select.value) params.append(`${type}[]`, select.value);
        }

        const query = params.toString();

        window.location.href = '/shop' + (query ? `?${query}` : '');
    });
});

/* Mobile shop: an infinite-scroll sentinel replaces page links. */
const infiniteSentinel = document.querySelector('[data-sf-infinite]');
const shopGrid = document.querySelector('.sf-shop__content .sf-product-grid');
const shopPagination = document.querySelector('.sf-shop__content .sf-pagination');

if (infiniteSentinel && shopGrid && shopPagination && window.matchMedia(MOBILE_SHOP_QUERY).matches) {
    let nextPageUrl = shopPagination.querySelector('a[rel="next"]')?.href ?? null;
    let loadingMore = false;

    shopPagination.hidden = true;
    infiniteSentinel.hidden = !nextPageUrl;

    const loadMore = async () => {
        if (!nextPageUrl || loadingMore) return;

        loadingMore = true;
        infiniteSentinel.hidden = false;

        try {
            const response = await fetch(nextPageUrl);

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const document_ = new DOMParser().parseFromString(await response.text(), 'text/html');

            document_.querySelectorAll('.sf-shop__content .sf-product-grid > *').forEach((card) => {
                shopGrid.appendChild(document.importNode(card, true));
            });

            nextPageUrl = document_.querySelector('.sf-shop__content .sf-pagination a[rel="next"]')?.href ?? null;
            infiniteSentinel.hidden = !nextPageUrl;
        } catch (error) {
            nextPageUrl = null;
            infiniteSentinel.hidden = true;
        } finally {
            loadingMore = false;
        }
    };

    new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) loadMore();
    }, { rootMargin: '400px 0px' }).observe(infiniteSentinel);
}

/* Mobile filter drawer. */
const sidebar = document.querySelector('[data-sf-sidebar]');
const sidebarToggles = document.querySelectorAll('[data-sf-mobile-filters]');
const sidebarBackdrop = document.querySelector('.sf-shop__backdrop');

function setSidebarOpen(open) {
    if (!sidebar) return;

    sidebar.classList.toggle('is-open', open);
    document.body.classList.toggle('sf-no-scroll', open);

    if (sidebarBackdrop) sidebarBackdrop.hidden = !open;
}

if (sidebar && sidebarToggles.length) {
    sidebarToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => setSidebarOpen(!sidebar.classList.contains('is-open')));
    });
}

document.querySelectorAll('[data-sf-sidebar-close]').forEach((element) => {
    element.addEventListener('click', () => setSidebarOpen(false));
});

/* Filter sections: long facets fetch their next page from the server; fixed
   option lists only reveal their hidden entries. */
const facetOptionInputName = (facet, filterSlug) => {
    if (facet === 'brands') return 'brands[]';
    if (facet === 'colors') return 'colors[]';
    if (facet === 'sizes') return 'sizes[]';

    return `filters[${filterSlug}][]`;
};

const facetLocalized = (value) => {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
        return value.az ?? value.en ?? Object.values(value)[0] ?? '';
    }

    return value ?? '';
};

const buildFacetOption = (facet, item, filterSlug) => {
    const rawValue = item.slug ?? item.value ?? item.id ?? '';
    const value = String(rawValue);
    const name = String(facetLocalized(item.name ?? rawValue));
    const count = Number(item.products_count ?? 0);
    const label = document.createElement('label');
    const input = document.createElement('input');

    input.type = 'checkbox';
    input.name = facetOptionInputName(facet, filterSlug);
    input.value = value;
    input.setAttribute('data-sf-auto-submit', '');
    label.appendChild(input);

    if (facet === 'sizes') {
        label.className = 'sf-size-option';

        const text = document.createElement('span');
        text.textContent = name;
        label.appendChild(text);

        const badge = document.createElement('span');
        badge.className = 'sf-size-option__count';
        badge.textContent = String(count);
        label.appendChild(badge);
    } else {
        label.className = 'sf-filter-option';

        const text = document.createElement('span');
        text.className = 'sf-filter-option__text';

        if (facet === 'colors') {
            const swatch = document.createElement('span');
            swatch.className = 'sf-swatch';
            swatch.style.backgroundColor = item.hex ?? '#cccccc';
            text.appendChild(swatch);
        }

        text.appendChild(document.createTextNode(name));
        label.appendChild(text);

        const badge = document.createElement('span');
        badge.className = 'sf-filter-option__count';
        badge.textContent = String(count);
        label.appendChild(badge);
    }

    return label;
};

document.querySelectorAll('[data-sf-show-more]').forEach((button) => {
    const card = button.closest('.sf-filter-card');
    if (!card) return;

    const facet = button.dataset.sfFacet;

    if (!facet) {
        button.addEventListener('click', () => {
            const extras = [...card.querySelectorAll('[data-sf-extra]')];
            const expanded = button.getAttribute('aria-expanded') === 'true';
            extras.forEach((option) => {
                option.hidden = expanded;
            });
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            button.textContent = expanded
                ? (button.dataset.moreLabel ?? 'Daha çox göstər')
                : (button.dataset.lessLabel ?? 'Daha az göstər');
        });

        return;
    }

    button.addEventListener('click', async () => {
        const container = card.querySelector('.sf-filter-options, .sf-size-grid');
        if (!container) return;

        const slug = button.dataset.sfSlug ?? '';
        const page = Number(button.dataset.sfPage ?? '2');
        const perPage = Number(button.dataset.sfPerPage ?? '10');
        const params = new URLSearchParams({
            facet,
            page: String(page),
            per_page: String(perPage),
        });

        if (button.dataset.sfCategory) params.set('category', button.dataset.sfCategory);
        if (facet === 'filter') params.set('slug', slug);

        button.disabled = true;

        try {
            const response = await fetch(`/shop/filters/values?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) throw new Error('facet fetch failed');

            const payload = await response.json();
            const data = payload.data ?? payload;
            const items = Array.isArray(data.items) ? data.items : [];
            const inputName = facetOptionInputName(facet, slug);

            items.forEach((item) => {
                const value = String(item.slug ?? item.value ?? item.id ?? '');
                if (!value) return;

                const exists = [...container.querySelectorAll('input')]
                    .some((input) => input.name === inputName && input.value === value);
                if (exists) return;

                container.appendChild(buildFacetOption(facet, item, slug));
            });

            const nextPage = page + 1;
            button.dataset.sfPage = String(nextPage);

            if (data.has_more) {
                const remaining = Math.max(0, Number(data.total ?? 0) - page * perPage);
                button.textContent = `Daha çox göstər${remaining ? ` (+${remaining})` : ''}`;
            } else {
                button.remove();
            }
        } catch (error) {
            button.hidden = true;
            card.querySelectorAll('[data-sf-extra]').forEach((option) => {
                option.hidden = false;
            });
        } finally {
            if (button.isConnected) button.disabled = false;
        }
    });
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
            const response = await fetch(`/shop/listings/${encodeURIComponent(contact.dataset.listing)}/contact`, {
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

    document.querySelectorAll('[data-sf-search]').forEach((search) => {
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
                const url = item.slug ? `/elan/${encodeURIComponent(item.slug)}` : '/shop';

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

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
        });

        document.addEventListener('click', (event) => {
            if (!search.contains(event.target)) close();
        });
    });

    /* Mobile search dropdown toggle */
    const mobileSearchToggle = document.querySelector('[data-sf-mobile-search-toggle]');
    const mobileSearchPanel = document.querySelector('[data-sf-mobile-search-panel]');
    if (mobileSearchToggle && mobileSearchPanel) {
        mobileSearchToggle.addEventListener('click', () => {
            const willOpen = mobileSearchPanel.hidden;
            mobileSearchPanel.hidden = !willOpen;
            mobileSearchToggle.setAttribute('aria-expanded', String(willOpen));
            mobileSearchToggle.classList.toggle('is-active', willOpen);
            if (willOpen) {
                const input = mobileSearchPanel.querySelector('[data-sf-search-input]');
                window.setTimeout(() => input?.focus(), 60);
            }
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

    if (drawer) {
        const openButtons = document.querySelectorAll('[data-sf-account-drawer-open]');
        const closeButtons = drawer.querySelectorAll('[data-sf-account-drawer-close]');

        const openDrawer = () => {
            drawer.hidden = false;
            document.body.style.overflow = 'hidden';
            openButtons.forEach((btn) => btn.setAttribute('aria-expanded', 'true'));
        };

        const closeDrawer = () => {
            drawer.hidden = true;
            document.body.style.overflow = '';
            openButtons.forEach((btn) => btn.setAttribute('aria-expanded', 'false'));
        };

        openButtons.forEach((button) => button.addEventListener('click', openDrawer));
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

/* Seller listings: promotion modal. */
(() => {
    const modal = document.querySelector('[data-sf-promote-modal]');
    if (!modal) return;

    const heading = modal.querySelector('[data-sf-promote-heading]');
    const form = modal.querySelector('[data-sf-promote-form]');
    const close = modal.querySelector('[data-sf-promote-close]');

    const open = (slug, title) => {
        if (form) form.action = `/elanlarim/${encodeURIComponent(slug)}/promote`;
        if (heading) heading.textContent = `İrəli çək: ${title}`;
        modal.hidden = false;
    };

    const hide = () => {
        modal.hidden = true;
    };

    document.querySelectorAll('[data-sf-promote-open]').forEach((button) => {
        button.addEventListener('click', () => {
            open(button.dataset.sfPromoteOpen, button.dataset.sfPromoteTitle || '');
        });
    });

    close?.addEventListener('click', hide);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) hide();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) hide();
    });
})();

/* Listing form: dependent filter values. */
(() => {
    const selects = [...document.querySelectorAll('[data-sf-filter]')];
    if (!selects.length) return;

    const resetDescendants = (filterId) => {
        selects.forEach((select) => {
            if (select.dataset.sfFilterDepends === String(filterId)) {
                select.value = '';
                select.disabled = true;
                resetDescendants(select.dataset.sfFilter);
            }
        });
    };

    const applyParent = (select) => {
        const parentId = select.dataset.sfFilterDepends;
        if (!parentId) return;

        const parent = document.querySelector(`[data-sf-filter="${parentId}"]`);
        const parentValue = parent?.value || '';
        const shouldEnable = parentValue !== '';

        select.disabled = !shouldEnable;
        [...select.options].forEach((option) => {
            if (!option.value) return;
            const optionParent = option.dataset.sfParentValue || '';
            const visible = !optionParent || optionParent === parentValue;
            option.hidden = !visible;
            if (!visible && option.selected) {
                select.value = '';
            }
        });
    };

    selects.forEach((select) => {
        applyParent(select);
        select.addEventListener('change', () => {
            resetDescendants(select.dataset.sfFilter);
            selects.forEach((child) => {
                if (child.dataset.sfFilterDepends) applyParent(child);
            });
        });
    });
})();

/* Listing form: image dropzone with previews. */
(() => {
    const zone = document.querySelector('[data-sf-dropzone]');
    const input = document.querySelector('[data-sf-dropzone-input]');
    const preview = document.querySelector('[data-sf-dropzone-preview]');
    if (!zone || !input || !preview) return;

    const render = () => {
        preview.innerHTML = '';
        [...input.files].forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'thumb';

            if (index === 0) {
                const tag = document.createElement('span');
                tag.className = 'cover-tag';
                tag.textContent = 'Əsas';
                item.appendChild(tag);
            }

            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = `Şəkil ${index + 1}`;
            item.appendChild(img);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'remove';
            remove.textContent = '×';
            remove.setAttribute('aria-label', 'Sil');
            remove.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const transfer = new DataTransfer();
                [...input.files].forEach((current, currentIndex) => {
                    if (currentIndex !== index) transfer.items.add(current);
                });
                input.files = transfer.files;
                render();
            });
            item.appendChild(remove);

            preview.appendChild(item);
        });
    };

    input.addEventListener('change', render);
    zone.addEventListener('dragover', (event) => {
        event.preventDefault();
        zone.classList.add('is-drag');
    });
    zone.addEventListener('dragleave', () => zone.classList.remove('is-drag'));
    zone.addEventListener('drop', (event) => {
        event.preventDefault();
        zone.classList.remove('is-drag');
        if (event.dataTransfer?.files?.length) {
            input.files = event.dataTransfer.files;
            render();
        }
    });
})();

/* Product review: star picker writes the chosen rating into the hidden input. */
document.querySelectorAll('[data-sf-rate]').forEach((picker) => {
    const input = picker.querySelector('[data-sf-rate-input]');
    const stars = Array.from(picker.querySelectorAll('[data-sf-rate-value]'));

    const paint = (value) => {
        stars.forEach((star) => {
            star.classList.toggle('is-active', Number(star.dataset.sfRateValue) <= value);
        });
    };

    stars.forEach((star) => {
        star.addEventListener('click', () => {
            const value = Number(star.dataset.sfRateValue);
            if (input) input.value = String(value);
            paint(value);
        });
    });

    paint(Number(input?.value || 5));
});

/* Buyer <-> seller chat modal on the product page. */
const listingChat = document.querySelector('[data-sf-lchat]');

if (listingChat) {
    const listEl = listingChat.querySelector('[data-sf-lchat-list]');
    const messagesEl = listingChat.querySelector('[data-sf-lchat-messages]');
    const headEl = listingChat.querySelector('[data-sf-lchat-thread-head]');
    const form = listingChat.querySelector('[data-sf-lchat-form]');
    const textarea = form?.querySelector('textarea');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let activeId = null;
    let conversations = [];

    const threadUrl = (id) => listingChat.dataset.threadUrl.replace('__ID__', String(id));
    const sendUrl = (id) => listingChat.dataset.sendUrl.replace('__ID__', String(id));

    const request = async (target, options = {}) => {
        const response = await fetch(target, {
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.method && options.method !== 'GET' ? { 'X-CSRF-TOKEN': csrf } : {}),
                ...(options.headers ?? {}),
            },
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const firstError = Object.values(data.errors ?? {}).flat()?.[0];
            throw new Error(firstError || data.message || 'Xəta baş verdi.');
        }

        return data;
    };

    const setOpen = (open) => {
        listingChat.hidden = !open;
        document.body.classList.toggle('sf-lchat-open', open);
        if (open) textarea?.focus();
    };

    const formatTime = (value) => {
        if (!value) return '';
        const date = new Date(value);
        return Number.isNaN(date.getTime())
            ? ''
            : date.toLocaleString('az-AZ', {
                  day: '2-digit',
                  month: '2-digit',
                  hour: '2-digit',
                  minute: '2-digit',
              });
    };

    const empty = (text) => {
        const p = document.createElement('p');
        p.className = 'sf-lchat__empty';
        p.textContent = text;
        return p;
    };

    const appendMessage = (message) => {
        const row = document.createElement('div');
        row.className = `sf-lchat__row${message.mine ? ' is-mine' : ''}`;

        const bubble = document.createElement('div');
        bubble.className = 'sf-lchat__bubble';
        bubble.textContent = message.body;

        const time = document.createElement('span');
        time.className = 'sf-lchat__time';
        time.textContent = formatTime(message.created_at);

        row.append(bubble, time);
        messagesEl.append(row);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    const renderThread = (thread) => {
        activeId = thread.conversation?.id ?? null;
        headEl.textContent = thread.conversation?.other?.name ?? 'Söhbət';

        messagesEl.textContent = '';

        if (!thread.messages?.length) {
            messagesEl.append(empty('Hələ mesaj yoxdur. İlk mesajı siz yazın.'));
        } else {
            thread.messages.forEach(appendMessage);
        }

        if (form) form.hidden = !activeId;
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    const renderList = () => {
        listEl.textContent = '';

        if (!conversations.length) {
            listEl.append(empty('Hələ söhbət yoxdur.'));
            return;
        }

        conversations.forEach((conversation) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = `sf-lchat__item${conversation.id === activeId ? ' is-active' : ''}`;
            item.dataset.id = String(conversation.id);

            const name = document.createElement('span');
            name.className = 'sf-lchat__item-name';
            name.textContent = conversation.other?.name ?? 'İstifadəçi';

            const last = document.createElement('span');
            last.className = 'sf-lchat__item-last';
            last.textContent = conversation.last_message || conversation.product?.title || '';

            item.append(name, last);
            listEl.append(item);
        });
    };

    const loadIndex = async () => {
        const data = await request(listingChat.dataset.indexUrl);
        conversations = Array.isArray(data) ? data : [];
        renderList();
    };

    const openThread = async (id) => {
        try {
            renderThread(await request(threadUrl(id)));
            await loadIndex();
        } catch (error) {
            messagesEl.textContent = '';
            messagesEl.append(empty(error.message));
        }
    };

    const openChat = async () => {
        setOpen(true);
        messagesEl.textContent = '';
        messagesEl.append(empty('Yüklənir…'));
        if (form) form.hidden = true;

        try {
            renderThread(await request(listingChat.dataset.startUrl, { method: 'POST' }));
            await loadIndex();
        } catch (error) {
            messagesEl.textContent = '';
            messagesEl.append(empty(error.message));
            await loadIndex().catch(() => {});
        }
    };

    document.querySelectorAll('[data-sf-lchat-open]').forEach((button) => {
        button.addEventListener('click', openChat);
    });

    listingChat.querySelectorAll('[data-sf-lchat-close]').forEach((element) => {
        element.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !listingChat.hidden) {
            setOpen(false);
        }
    });

    listEl.addEventListener('click', (event) => {
        const item = event.target.closest('[data-sf-lchat-item]') ?? event.target.closest('.sf-lchat__item');
        if (item?.dataset.id) {
            openThread(Number(item.dataset.id));
        }
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const body = textarea?.value.trim();
        if (!body || !activeId) return;

        const submit = form.querySelector('button[type="submit"]');
        if (submit) submit.disabled = true;

        try {
            appendMessage(
                await request(sendUrl(activeId), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ body }),
                })
            );
            textarea.value = '';
            await loadIndex();
        } catch (error) {
            messagesEl.append(empty(error.message));
        } finally {
            if (submit) submit.disabled = false;
        }
    });
}

/* Store team: copy invitation links. */
(() => {
    const buttons = document.querySelectorAll('[data-sf-copy]');
    if (!buttons.length) return;

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.dataset.sfCopy || '';

            try {
                await navigator.clipboard.writeText(text);
                const original = button.textContent;
                button.classList.add('is-copied');
                button.textContent = 'Kopyalandı';
                setTimeout(() => {
                    button.textContent = original;
                    button.classList.remove('is-copied');
                }, 1600);
            } catch {
                const source = button.parentElement?.querySelector('[data-sf-copy-source]');
                if (source) {
                    source.select();
                    document.execCommand('copy');
                }
            }
        });
    });
})();
