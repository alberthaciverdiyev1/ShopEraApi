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

/* Shop categories: the root picker always navigates, and the subcategory
   picker only exists once a root is chosen. Both rebuild the URL from the
   filter form so the other selected filters survive, and they navigate in the
   mobile drawer too instead of waiting for the apply button. */
document.querySelectorAll('[data-sf-category-root]').forEach((rootSelect) => {
    const form = rootSelect.closest('form');

    if (!form) return;

    const childSelect = form.querySelector('[data-sf-category-child]');

    const navigate = (category) => {
        const params = new URLSearchParams(new FormData(form));
        params.delete('category');
        params.delete('page');

        if (category) params.set('category', category);

        const query = params.toString();
        window.location.href = (form.getAttribute('action') || '/shop') + (query ? `?${query}` : '');
    };

    rootSelect.addEventListener('change', () => {
        // On phones the filter drawer closes on navigation; reopen it on the
        // reloaded page so the subcategory picker is one tap away.
        try {
            if (rootSelect.value && window.matchMedia(MOBILE_SHOP_QUERY).matches) {
                sessionStorage.setItem('sf-pick-subcategory', '1');
            }
        } catch (error) {
            // Storage can be unavailable; the picker is still reachable.
        }

        navigate(rootSelect.value);
    });

    if (childSelect) {
        // A chosen subcategory wins over its root; an empty one falls back to it.
        childSelect.addEventListener('change', () => navigate(childSelect.value || rootSelect.value));
    }
});

// Reopen the mobile filter drawer when the last root pick sent us here.
try {
    if (sessionStorage.getItem('sf-pick-subcategory') === '1') {
        sessionStorage.removeItem('sf-pick-subcategory');

        if (window.matchMedia(MOBILE_SHOP_QUERY).matches && document.querySelector('[data-sf-category-child]')) {
            window.setTimeout(() => document.querySelector('[data-sf-mobile-filters]')?.click(), 0);
        }
    }
} catch (error) {
    // Storage can be unavailable; nothing to reopen.
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

/* Custom selects: the native element still holds the value and submits with
   the form, while a listbox matching the storefront design is rendered on
   top. Multi-selects and opt-outs stay native. */
document.querySelectorAll('select').forEach((select) => {
    if (select.multiple || select.dataset.sfNative !== undefined || select.closest('.sf-select')) return;

    const options = Array.from(select.options);

    if (!options.length) return;

    const inline = select.closest('.trendyol-pill-select');
    const root = document.createElement('div');
    root.className = 'sf-select' + (inline ? ' sf-select--inline' : '');

    select.parentNode.insertBefore(root, select);
    root.appendChild(select);
    select.classList.add('sf-select__native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'sf-select__trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const valueEl = document.createElement('span');
    valueEl.className = 'sf-select__value';
    trigger.appendChild(valueEl);

    const chevron = document.createElement('i');
    chevron.className = 'fa-regular fa-chevron-down sf-select__chevron';
    chevron.setAttribute('aria-hidden', 'true');
    trigger.appendChild(chevron);
    root.appendChild(trigger);

    // The menu lives on <body> in a fixed layer so scroll containers (the
    // filter sidebar, the quick-filter strip) cannot clip it.
    const menu = document.createElement('ul');
    menu.className = 'sf-select__menu sf-select__menu--portal';
    menu.setAttribute('role', 'listbox');
    menu.hidden = true;
    document.body.appendChild(menu);

    const items = options.map((option, index) => {
        const item = document.createElement('li');
        item.className = 'sf-select__option';
        item.setAttribute('role', 'option');
        item.tabIndex = -1;

        const text = document.createElement('span');
        text.textContent = option.textContent.trim();
        item.appendChild(text);

        const check = document.createElement('i');
        check.className = 'fa-solid fa-check sf-select__check';
        check.setAttribute('aria-hidden', 'true');
        item.appendChild(check);

        if (option.disabled) item.classList.add('is-disabled');

        item.addEventListener('click', () => {
            if (option.disabled) return;

            select.selectedIndex = index;
            sync();
            toggle(false);
            trigger.focus();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });

        menu.appendChild(item);

        return item;
    });

    function sync() {
        const selected = select.options[select.selectedIndex];
        valueEl.textContent = selected ? selected.textContent.trim() : '';
        trigger.disabled = select.disabled;

        items.forEach((item, index) => {
            const isSelected = index === select.selectedIndex;
            item.classList.toggle('is-selected', isSelected);
            item.setAttribute('aria-selected', String(isSelected));
        });
    }

    function positionMenu() {
        const rect = trigger.getBoundingClientRect();
        const width = Math.min(Math.max(rect.width, 220), window.innerWidth - 16);
        const left = Math.max(8, Math.min(rect.left, window.innerWidth - 8 - width));

        menu.style.width = `${width}px`;
        menu.style.left = `${left}px`;

        const height = menu.offsetHeight;
        const below = window.innerHeight - rect.bottom - 8;

        menu.style.top = height + 6 > below && rect.top - height - 6 > 8
            ? `${rect.top - height - 6}px`
            : `${rect.bottom + 6}px`;
    }

    function toggle(open) {
        menu.hidden = !open;
        root.classList.toggle('is-open', open);
        trigger.setAttribute('aria-expanded', String(open));

        if (open) positionMenu();
    }

    const reposition = () => {
        if (!menu.hidden) positionMenu();
    };

    window.addEventListener('scroll', reposition, true);
    window.addEventListener('resize', reposition);

    trigger.addEventListener('click', () => toggle(menu.hidden));
    trigger.addEventListener('keydown', (event) => {
        if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) return;

        event.preventDefault();
        toggle(true);
        (items[select.selectedIndex] ?? items[0])?.focus();
    });

    menu.addEventListener('keydown', (event) => {
        const active = document.activeElement;
        const current = items.indexOf(active);

        if (event.key === 'Escape') {
            toggle(false);
            trigger.focus();
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            items[(current + 1) % items.length]?.focus();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            items[(current - 1 + items.length) % items.length]?.focus();
        } else if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            active?.click();
        }
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target) && !menu.contains(event.target)) toggle(false);
    });

    if (inline) inline.classList.add('is-enhanced');

    sync();
});

/* Mobile shop: Sort modal bottom sheet */
const sortModal = document.querySelector('[data-sf-sort-modal]');
const sortModalOpens = document.querySelectorAll('[data-sf-sort-modal-open]');
const sortModalCloses = document.querySelectorAll('[data-sf-sort-close]');

if (sortModal) {
    const openSortModal = () => {
        sortModal.hidden = false;
        document.body.style.overflow = 'hidden';
    };

    const closeSortModal = () => {
        sortModal.hidden = true;
        document.body.style.overflow = '';
    };

    sortModalOpens.forEach((btn) => btn.addEventListener('click', openSortModal));
    sortModalCloses.forEach((btn) => btn.addEventListener('click', closeSortModal));

    sortModal.querySelectorAll('[data-sf-sort-choose]').forEach((item) => {
        item.addEventListener('click', () => {
            const sortVal = item.dataset.sfSortChoose;
            const params = new URLSearchParams(window.location.search);
            params.delete('page');
            params.set('sort', sortVal);
            closeSortModal();
            window.location.href = '/shop?' + params.toString();
        });
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !sortModal.hidden) closeSortModal();
    });
}

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

/* Product gallery: swipeable slider, dots, thumbnails, arrows, share, fav, and lightbox. */
const gallery = document.querySelector('[data-sf-gallery]');

if (gallery) {
    const stage = gallery.querySelector('[data-sf-gallery-stage]');
    const image = gallery.querySelector('[data-sf-gallery-image]');
    const thumbs = Array.from(gallery.querySelectorAll('[data-sf-gallery-thumb]'));
    const dots = Array.from(gallery.querySelectorAll('.sf-gallery__dot'));
    const lightbox = gallery.querySelector('[data-sf-lightbox]');
    const lightboxImage = gallery.querySelector('[data-sf-lightbox-image]');
    const lightboxThumbs = Array.from(gallery.querySelectorAll('[data-sf-lightbox-thumb]'));
    const lightboxTotal = lightbox?.querySelector('[data-sf-lightbox-total]');

    // Photos may be tied to a colour and a size; the gallery only shows the
    // shots that match the variant picked below it.
    let activeColor = null;
    let activeSize = null;
    let visible = thumbs.map((_, index) => index);
    let current = 0;

    const counterCurrent = lightbox?.querySelector('[data-sf-lightbox-current]');

    const matches = (element) => {
        const color = element?.dataset.colorId || '';
        const size = element?.dataset.sizeId || '';

        if (activeColor && color && color !== activeColor) return false;
        if (activeSize && size && size !== activeSize) return false;

        return true;
    };

    const show = (position) => {
        if (visible.length === 0) return;

        current = (position + visible.length) % visible.length;

        const index = visible[current];
        const src = thumbs[index]?.dataset.src || lightboxThumbs[index]?.querySelector('img')?.src;
        if (src) {
            if (image) image.src = src;
            if (lightboxImage) lightboxImage.src = src;
        }

        thumbs.forEach((thumb, position) => thumb.classList.toggle('is-active', position === index));
        lightboxThumbs.forEach((thumb, position) => thumb.classList.toggle('is-active', position === index));
        dots.forEach((dot, position) => dot.classList.toggle('is-active', position === index));
        if (counterCurrent) counterCurrent.textContent = String(current + 1);

        if (thumbs[index] && typeof thumbs[index].scrollIntoView === 'function') {
            thumbs[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        if (lightboxThumbs[index] && typeof lightboxThumbs[index].scrollIntoView === 'function') {
            lightboxThumbs[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    };

    const applyFilter = () => {
        if (thumbs.length === 0) return;

        const matched = thumbs.map((_, index) => index).filter((index) => matches(thumbs[index]));

        // Never leave the stage empty: fall back to every photo.
        visible = matched.length > 0 ? matched : thumbs.map((_, index) => index);

        thumbs.forEach((thumb, index) => {
            thumb.style.display = visible.includes(index) ? '' : 'none';
        });
        dots.forEach((dot, index) => {
            dot.style.display = visible.includes(index) ? '' : 'none';
        });
        lightboxThumbs.forEach((thumb, index) => {
            thumb.style.display = visible.includes(index) ? '' : 'none';
        });

        if (lightboxTotal) lightboxTotal.textContent = String(visible.length);

        show(0);
    };

    thumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', () => show(visible.indexOf(index)));
    });

    lightboxThumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', (e) => {
            e.stopPropagation();
            show(visible.indexOf(index));
        });
    });

    dots.forEach((dot) => {
        dot.addEventListener('click', () => show(visible.indexOf(Number(dot.dataset.index))));
    });

    document.addEventListener('sf:variant-change', (event) => {
        const detail = event.detail || {};

        if ('colorId' in detail) activeColor = detail.colorId || null;
        if ('sizeId' in detail) activeSize = detail.sizeId || null;

        applyFilter();
    });

    // Start on the first colour/size the summary marks as active.
    activeColor = document.querySelector('[data-sf-color-option].is-active')?.dataset.colorId || null;
    activeSize = document.querySelector('[data-sf-size-option].is-active')?.dataset.sizeId || null;
    applyFilter();

    gallery.querySelector('[data-sf-gallery-prev]')?.addEventListener('click', () => show(current - 1));
    gallery.querySelector('[data-sf-gallery-next]')?.addEventListener('click', () => show(current + 1));

    // Touch Swipe Support (Mobile Gallery Slider)
    if (stage) {
        let touchStartX = 0;
        let touchStartY = 0;

        stage.addEventListener('touchstart', (event) => {
            if (event.touches.length === 1) {
                touchStartX = event.touches[0].clientX;
                touchStartY = event.touches[0].clientY;
            }
        }, { passive: true });

        stage.addEventListener('touchend', (event) => {
            if (event.changedTouches.length === 1) {
                const diffX = event.changedTouches[0].clientX - touchStartX;
                const diffY = event.changedTouches[0].clientY - touchStartY;

                // Only horizontal swipes
                if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY)) {
                    if (diffX < 0) {
                        show(current + 1);
                    } else {
                        show(current - 1);
                    }
                }
            }
        }, { passive: true });
    }

    // Floating Share Button
    const shareBtn = gallery.querySelector('[data-sf-share-btn]');
    if (shareBtn) {
        shareBtn.addEventListener('click', async () => {
            const title = shareBtn.dataset.title || document.title;
            const url = window.location.href;

            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                } catch (err) {
                    // Ignore share cancellation
                }
            } else {
                try {
                    await navigator.clipboard.writeText(url);
                    alert('Məhsul linki kopyalandı!');
                } catch (err) {
                    // Fallback
                }
            }
        });
    }

    if (lightbox && lightboxImage) {
        gallery.querySelector('[data-sf-gallery-open]')?.addEventListener('click', () => {
            show(current);
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

        lightbox.querySelector('[data-sf-lightbox-prev]')?.addEventListener('click', (e) => {
            e.stopPropagation();
            show(current - 1);
        });

        lightbox.querySelector('[data-sf-lightbox-next]')?.addEventListener('click', (e) => {
            e.stopPropagation();
            show(current + 1);
        });

        // Touch Swipe Support for Lightbox (Mobile Fullscreen Swipe)
        let lbTouchStartX = 0;
        let lbTouchStartY = 0;

        lightbox.addEventListener('touchstart', (event) => {
            if (event.touches.length === 1) {
                lbTouchStartX = event.touches[0].clientX;
                lbTouchStartY = event.touches[0].clientY;
            }
        }, { passive: true });

        lightbox.addEventListener('touchend', (event) => {
            if (event.changedTouches.length === 1) {
                const diffX = event.changedTouches[0].clientX - lbTouchStartX;
                const diffY = event.changedTouches[0].clientY - lbTouchStartY;

                if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY)) {
                    if (diffX < 0) {
                        show(current + 1);
                    } else {
                        show(current - 1);
                    }
                }
            }
        }, { passive: true });

        document.addEventListener('keydown', (event) => {
            if (lightbox.hidden) return;

            if (event.key === 'Escape') closeLightbox();
            if (event.key === 'ArrowLeft') show(current - 1);
            if (event.key === 'ArrowRight') show(current + 1);
        });
    }
}

/* Favorite buttons: persist through POST /wishlist/{slug}. */
function applyFavoriteState(favorited, slug) {
    document.querySelectorAll('[data-sf-fav-toggle]').forEach((item) => {
        if (slug && item.dataset.slug && item.dataset.slug !== slug) {
            return;
        }

        item.classList.toggle('is-favorited', favorited);
        item.setAttribute('aria-label', favorited ? 'Sevimlilərdən çıxar' : 'Sevimlilərə əlavə et');

        const icon = item.querySelector('i');
        if (icon) {
            icon.className = favorited ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
        }

        const span = item.querySelector('span');
        if (span) {
            span.textContent = favorited ? 'Sevimlilərdən çıxar' : 'Sevimlilərə əlavə et';
        }
    });
}

document.querySelectorAll('[data-sf-fav-toggle]').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
        e.preventDefault();

        if (btn.dataset.auth !== '1') {
            window.location.href = '/login';
            return;
        }

        const slug = btn.dataset.slug;
        if (!slug || btn.dataset.loading === '1') {
            return;
        }

        btn.dataset.loading = '1';

        try {
            const response = await fetch(`/wishlist/${encodeURIComponent(slug)}`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
            });

            if (response.redirected) {
                window.location.href = '/login';
                return;
            }

            const payload = await response.json().catch(() => null);

            if (!response.ok || !payload?.success) {
                throw new Error(payload?.message || 'Request failed');
            }

            applyFavoriteState(Boolean(payload.favorited), slug);
        } catch (error) {
            alert('İstək siyahısı yenilənə bilmədi. Yenidən cəhd edin.');
        } finally {
            delete btn.dataset.loading;
        }
    });
});

/* Product variants: colour and size selection drive the gallery and the price. */
(() => {
    const colorOptions = document.querySelectorAll('[data-sf-color-option]');
    const sizeOptions = document.querySelectorAll('[data-sf-size-option]');

    if (colorOptions.length === 0 && sizeOptions.length === 0) return;

    const priceCurrent = document.querySelectorAll('[data-sf-price-current]');
    const priceOld = document.querySelectorAll('[data-sf-price-old]');
    const priceBadge = document.querySelector('[data-sf-price-badge]');
    const stockBadge = document.querySelector('[data-sf-stock-badge]');
    const stockLine = document.querySelector('[data-sf-stock-line]');

    let sizePrices = {};
    let generalStock = null;

    try {
        const sizePricesNode = document.querySelector('[data-sf-size-prices]');
        sizePrices = JSON.parse(sizePricesNode?.dataset.sfSizePrices || '{}');
        generalStock = sizePricesNode?.dataset.sfGeneralStock ?? null;
        generalStock = generalStock === null ? null : Number(generalStock);
    } catch (error) {
        sizePrices = {};
    }

    const money = (value) => `${Number(value).toFixed(2)} ₼`;

    const paintStock = (stock) => {
        if (!stockBadge) return;

        if (stock === null || Number.isNaN(stock)) {
            stockLine?.setAttribute('hidden', '');
            return;
        }

        stockLine?.removeAttribute('hidden');
        stockBadge.textContent = stock > 0 ? `Stokda: ${stock} ədəd` : 'Stokda yoxdur';
        stockBadge.classList.toggle('is-out', stock <= 0);
    };

    const paintPrice = (current, original, percent) => {
        priceCurrent.forEach((element) => {
            element.textContent = money(current);
        });

        priceOld.forEach((element) => {
            element.textContent = money(original ?? 0);
            element.hidden = original === null;
        });

        if (priceBadge) {
            priceBadge.textContent = `-${percent}%`;
            priceBadge.hidden = !percent;
        }
    };

    const selectSizePrice = (sizeId) => {
        const entry = sizePrices[String(sizeId)];

        if (!entry) return;

        // A size without its own stock falls back to the listing's total.
        paintStock(entry.stock !== null && entry.stock !== undefined ? entry.stock : generalStock);

        if (!(entry.price > 0)) return;

        const price = entry.price;
        const hasDiscount = entry.discount > 0 && entry.discount < price;
        const current = hasDiscount ? entry.discount : price;
        const percent = hasDiscount ? Math.round(((price - current) / price) * 100) : 0;

        paintPrice(current, hasDiscount ? price : null, percent);
    };

    colorOptions.forEach((option) => {
        option.addEventListener('click', () => {
            colorOptions.forEach((opt) => {
                opt.classList.remove('is-active');
                opt.setAttribute('aria-checked', 'false');
            });
            option.classList.add('is-active');
            option.setAttribute('aria-checked', 'true');

            const label = document.querySelector('[data-sf-color-label]');
            if (label && option.dataset.colorName) {
                label.textContent = option.dataset.colorName;
            }

            document.dispatchEvent(new CustomEvent('sf:variant-change', {
                detail: { colorId: option.dataset.colorId || null },
            }));
        });
    });

    sizeOptions.forEach((option) => {
        option.addEventListener('click', () => {
            sizeOptions.forEach((opt) => {
                opt.classList.remove('is-active');
                opt.setAttribute('aria-checked', 'false');
            });
            option.classList.add('is-active');
            option.setAttribute('aria-checked', 'true');

            const label = document.querySelector('[data-sf-size-label]');
            if (label && option.dataset.sizeName) {
                label.textContent = option.dataset.sizeName;
            }

            selectSizePrice(option.dataset.sizeId);

            document.dispatchEvent(new CustomEvent('sf:variant-change', {
                detail: { sizeId: option.dataset.sizeId || null },
            }));
        });
    });

    // The first size is active on load; show its price.
    if (sizeOptions.length > 0) {
        selectSizePrice(sizeOptions[0].dataset.sizeId);
    }
})();

/* Modal handler for Size Chart */
document.querySelectorAll('[data-sf-modal-open]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.sfModalOpen;
        const modal = document.getElementById(id);
        if (modal) {
            modal.hidden = false;
            document.body.classList.add('sf-no-scroll');
        }
    });
});

document.querySelectorAll('[data-sf-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const modal = btn.closest('.sf-modal');
        if (modal) {
            modal.hidden = true;
            document.body.classList.remove('sf-no-scroll');

            const video = modal.querySelector('video');
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
        }
    });
});

/* Listing video modal */
document.querySelectorAll('[data-sf-video-open]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const modal = document.getElementById('sf-video-modal');
        const player = modal?.querySelector('[data-sf-video-player]');

        if (!modal || !player) {
            return;
        }

        if (btn.dataset.videoSrc && player.getAttribute('src') !== btn.dataset.videoSrc) {
            player.setAttribute('src', btn.dataset.videoSrc);
        }

        modal.hidden = false;
        document.body.classList.add('sf-no-scroll');
        player.play?.().catch(() => {});
    });
});

/* Jump to reviews tab on rating bar click */
document.querySelectorAll('[data-sf-goto-reviews]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        const tabBtn = document.querySelector('[data-sf-tab="reviews"]');
        if (tabBtn) {
            tabBtn.click();
            tabBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});

/* Sticky mobile button connects to primary reveal */
document.querySelectorAll('.sf-product-sticky-bottom [data-sf-contact-reveal]').forEach((stickyBtn) => {
    stickyBtn.addEventListener('click', () => {
        const mainRevealBtn = document.querySelector('.sf-product-contact [data-sf-contact-reveal]');
        if (mainRevealBtn && !mainRevealBtn.hidden) {
            mainRevealBtn.click();
            mainRevealBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});

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

            if (data.phone) {
                document.querySelectorAll('.sf-product-sticky-bottom [data-sf-contact-reveal]').forEach((stickyBtn) => {
                    stickyBtn.innerHTML = `<i class="fa-solid fa-phone"></i> ${escape(data.phone)}`;
                    stickyBtn.addEventListener('click', (ev) => {
                        ev.stopPropagation();
                        window.location.href = `tel:${escape(data.phone)}`;
                    });
                });
            }
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

    /* Categories Page: 3-Column Explorer & Mobile 2-Panel Rail */
    const catExplorer = document.querySelector('[data-sf-page-catalog]');
    if (catExplorer) {
        const parentLinks = Array.from(catExplorer.querySelectorAll('[data-cat-parent]'));
        const childColumns = Array.from(catExplorer.querySelectorAll('[data-cat-children]'));
        const leafColumns = Array.from(catExplorer.querySelectorAll('[data-cat-leaves]'));

        const activateChild = (id) => {
            catExplorer.querySelectorAll('[data-cat-child]').forEach((link) => {
                link.classList.toggle('is-active', link.dataset.catChild === String(id));
            });

            leafColumns.forEach((col) => {
                col.hidden = col.dataset.catLeaves !== String(id);
            });
        };

        const activateParent = (id) => {
            parentLinks.forEach((link) => {
                link.classList.toggle('is-active', link.dataset.catParent === String(id));
            });

            childColumns.forEach((col) => {
                col.hidden = col.dataset.catChildren !== String(id);
            });

            const activeSubgroup = catExplorer.querySelector(`[data-cat-children="${id}"]`);
            const firstChild = activeSubgroup?.querySelector('[data-cat-child]');
            if (firstChild) {
                activateChild(firstChild.dataset.catChild);
            } else {
                leafColumns.forEach((col) => { col.hidden = true; });
            }
        };

        parentLinks.forEach((link) => {
            const id = link.dataset.catParent;
            link.addEventListener('mouseenter', () => activateParent(id));
            link.addEventListener('click', (e) => {
                e.preventDefault();
                activateParent(id);
            });
        });

        catExplorer.querySelectorAll('[data-cat-child]').forEach((link) => {
            const id = link.dataset.catChild;
            link.addEventListener('mouseenter', () => activateChild(id));
            link.addEventListener('click', (e) => {
                e.preventDefault();
                activateChild(id);
            });
        });
    }

    const mobileCatExplorer = document.querySelector('[data-sf-mobile-cat-explorer]');
    if (mobileCatExplorer) {
        const railButtons = Array.from(mobileCatExplorer.querySelectorAll('[data-sf-mobile-cat-btn]'));
        const contentPanes = Array.from(mobileCatExplorer.querySelectorAll('[data-sf-mobile-cat-pane]'));

        railButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.sfMobileCatBtn;

                railButtons.forEach((b) => b.classList.toggle('is-active', b === btn));
                contentPanes.forEach((pane) => {
                    pane.hidden = pane.dataset.sfMobileCatPane !== id;
                });
            });
        });
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

        /* Trendyol category drawer interactions */
        const rootTabs = drawer.querySelectorAll('[data-sf-drawer-root-tab]');
        const rootPanels = drawer.querySelectorAll('[data-sf-drawer-root-panel]');

        const activateSub = (panel, subId) => {
            const subTabs = panel.querySelectorAll('[data-sf-drawer-sub-tab]');
            const subPanels = panel.querySelectorAll('[data-sf-drawer-sub-panel]');

            subTabs.forEach((tab) => {
                tab.classList.toggle('is-active', tab.dataset.sfDrawerSubTab === String(subId));
            });

            subPanels.forEach((subPanel) => {
                const isCurrent = subPanel.dataset.sfDrawerSubPanel === String(subId);
                subPanel.hidden = !isCurrent;
                subPanel.classList.toggle('is-active', isCurrent);
            });
        };

        const activateRoot = (rootId) => {
            rootTabs.forEach((tab) => {
                const isCurrent = tab.dataset.sfDrawerRootTab === String(rootId);
                tab.classList.toggle('is-active', isCurrent);
                tab.setAttribute('aria-selected', String(isCurrent));
                if (isCurrent) {
                    tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            });

            rootPanels.forEach((panel) => {
                const isCurrent = panel.dataset.sfDrawerRootPanel === String(rootId);
                panel.hidden = !isCurrent;
                panel.classList.toggle('is-active', isCurrent);

                if (isCurrent) {
                    const activeSubTab = panel.querySelector('[data-sf-drawer-sub-tab].is-active')
                        || panel.querySelector('[data-sf-drawer-sub-tab]');
                    if (activeSubTab) {
                        activateSub(panel, activeSubTab.dataset.sfDrawerSubTab);
                    }
                }
            });
        };

        rootTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                activateRoot(tab.dataset.sfDrawerRootTab);
            });
        });

        drawer.querySelectorAll('[data-sf-drawer-sub-tab]').forEach((subTab) => {
            subTab.addEventListener('click', () => {
                const panel = subTab.closest('[data-sf-drawer-root-panel]');
                if (panel) {
                    activateSub(panel, subTab.dataset.sfDrawerSubTab);
                }
            });
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

/* Listing form: Trendyol-style automatic title. */
(() => {
    const form = document.querySelector('[data-sf-listing-form]');
    const input = form?.querySelector('[data-sf-title-input]');
    const toggle = form?.querySelector('[data-sf-auto-title-toggle]');
    if (!form || !input || !toggle) return;

    const category = form.dataset.sfCategory || '';
    const brand = form.querySelector('[name="brand_id"]');
    const model = form.querySelector('[name="model"]');

    const pickedFilters = () => [...form.querySelectorAll('[name^="filters["]')]
        .flatMap((field) => {
            if (field.tagName === 'SELECT') {
                return field.value ? [field.options[field.selectedIndex]?.textContent?.trim()] : [];
            }

            return [...field.querySelectorAll('input[type="checkbox"]:checked')]
                .map((box) => box.value.trim());
        })
        .filter(Boolean);

    const generate = () => {
        const brandText = brand && brand.value ? brand.options[brand.selectedIndex]?.textContent?.trim() : '';
        const parts = [brandText, model?.value?.trim(), ...pickedFilters().slice(0, 2), category]
            .filter(Boolean);

        return [...new Set(parts)].join(' ').replace(/\s+/g, ' ').trim();
    };

    const sync = () => {
        input.readOnly = toggle.checked;
        input.classList.toggle('is-auto', toggle.checked);
        input.required = !toggle.checked && input.dataset.required === '1';

        if (toggle.checked) {
            input.value = generate();
        }
    };

    form.addEventListener('input', () => {
        if (toggle.checked) input.value = generate();
    });
    form.addEventListener('change', () => {
        if (toggle.checked) input.value = generate();
    });
    toggle.addEventListener('change', sync);
    sync();
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

/* Listing form: a size is editable once selected. While the standard price
   switch is on, the per-size stock and price fields are hidden and disabled. */
(() => {
    const rows = [...document.querySelectorAll('[data-sf-size-row]')];
    const standard = document.querySelector('[data-sf-standard-price]');
    if (rows.length === 0) return;

    const sync = () => {
        const standardOn = Boolean(standard?.checked);

        rows.forEach((row) => {
            const selected = Boolean(row.querySelector('[data-sf-size-check]')?.checked);

            row.querySelectorAll('[data-sf-size-stock]').forEach((input) => {
                input.disabled = !selected || standardOn;
                input.hidden = standardOn;
            });

            // With the standard price on the per-size price fields are not
            // used at all, so they are hidden as well as disabled.
            row.querySelectorAll('[data-sf-size-price]').forEach((input) => {
                input.disabled = !selected || standardOn;
                input.hidden = standardOn;
            });
        });
    };

    rows.forEach((row) => {
        row.querySelector('[data-sf-size-check]')?.addEventListener('change', sync);
    });
    standard?.addEventListener('change', sync);
    sync();
})();

/* Listing form wizard: one section per step with Geri / Növbəti buttons; the
   submit button only appears on the last step. */
(() => {
    const form = document.querySelector('[data-sf-listing-form]');
    const steps = [...document.querySelectorAll('[data-sf-wizard-step]')];
    if (!form || steps.length < 2) return;

    const rail = document.querySelector('[data-sf-wizard-progress]');
    const dots = [...document.querySelectorAll('[data-sf-wizard-dot]')];
    const bars = [...document.querySelectorAll('[data-sf-wizard-bar]')];
    const prev = form.querySelector('[data-sf-wizard-prev]');
    const next = form.querySelector('[data-sf-wizard-next]');
    const submit = form.querySelector('[data-sf-wizard-submit]');
    let index = 0;

    const invalidField = (step) => [...step.querySelectorAll('input, select, textarea')]
        .find((field) => !field.disabled && !field.hidden && !field.closest('[hidden]') && !field.checkValidity());

    const show = (target) => {
        index = Math.min(Math.max(target, 0), steps.length - 1);

        steps.forEach((step, n) => {
            step.hidden = n !== index;
        });
        dots.forEach((dot, n) => {
            dot.classList.toggle('active', n === index);
            dot.classList.toggle('done', n < index);
        });
        // The bar before each step fills once that step has been reached.
        bars.forEach((bar, n) => {
            bar.classList.toggle('filled', n <= index);
        });

        if (prev) prev.hidden = index === 0;
        if (next) next.hidden = index === steps.length - 1;
        if (submit) submit.hidden = index !== steps.length - 1;

        // Keep the progress rail and the new step in view; the rail's
        // scroll-margin clears the sticky site header.
        (rail || form).scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const advance = () => {
        const bad = invalidField(steps[index]);
        if (bad) {
            bad.reportValidity();
            bad.focus();
            return;
        }

        show(index + 1);
    };

    next?.addEventListener('click', advance);
    prev?.addEventListener('click', () => show(index - 1));

    // Enter in a field should move to the next step, not submit early.
    form.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.target.tagName === 'TEXTAREA') return;
        if (index === steps.length - 1) return;
        event.preventDefault();
        advance();
    });

    // After a failed server-side validation, open the first step with an
    // invalid field so the error is visible; otherwise start at the top.
    if (form.dataset.sfHasErrors === '1') {
        const bad = steps.findIndex((step) => invalidField(step) !== undefined);
        show(bad >= 0 ? bad : steps.length - 1);
    } else {
        show(0);
    }
})();

/* Listing form: image dropzone with previews and per-photo colour/size. */
(() => {
    const zone = document.querySelector('[data-sf-dropzone]');
    const input = document.querySelector('[data-sf-dropzone-input]');
    const preview = document.querySelector('[data-sf-dropzone-preview]');
    if (!zone || !input || !preview) return;

    let colors = [];
    let sizes = [];

    try {
        colors = JSON.parse(preview.dataset.sfColors || '[]');
        sizes = JSON.parse(preview.dataset.sfSizes || '[]');
    } catch (error) {
        colors = [];
        sizes = [];
    }

    // Per-photo choices, kept by file index so removing a photo keeps the rest.
    let meta = [];

    const checkedValues = (name) => [...document.querySelectorAll(`input[name="${name}"]:checked`)]
        .map((element) => String(element.value));

    const optionsFor = (all, selected) => (
        selected.length > 0 ? all.filter((option) => selected.includes(String(option.id))) : all
    );

    const buildSelect = (index, field, options, placeholder) => {
        const select = document.createElement('select');
        select.name = field === 'color' ? 'image_colors[]' : 'image_sizes[]';
        select.className = 'thumb-select';
        select.setAttribute('aria-label', placeholder);

        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = placeholder;
        select.appendChild(empty);

        options.forEach((option) => {
            const item = document.createElement('option');
            item.value = String(option.id);
            item.textContent = option.name;
            select.appendChild(item);
        });

        const stored = meta[index]?.[field];
        select.value = stored != null && options.some((option) => String(option.id) === String(stored))
            ? String(stored)
            : '';

        select.addEventListener('change', () => {
            meta[index] = { ...(meta[index] || {}), [field]: select.value };
        });

        return select;
    };

    const render = () => {
        preview.innerHTML = '';

        [...input.files].forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'thumb';

            const picture = document.createElement('div');
            picture.className = 'thumb-pic';

            if (index === 0) {
                const tag = document.createElement('span');
                tag.className = 'cover-tag';
                tag.textContent = 'Əsas';
                picture.appendChild(tag);
            }

            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = `Şəkil ${index + 1}`;
            picture.appendChild(img);

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
                meta.splice(index, 1);
                render();
            });
            picture.appendChild(remove);

            item.appendChild(picture);

            const metaRow = document.createElement('div');
            metaRow.className = 'thumb-meta';

            const colorOptions = optionsFor(colors, checkedValues('colors[]'));
            const sizeOptions = optionsFor(sizes, checkedValues('sizes[]'));

            if (colorOptions.length > 0) {
                metaRow.appendChild(buildSelect(index, 'color', colorOptions, 'Rəng'));
            }

            if (sizeOptions.length > 0) {
                metaRow.appendChild(buildSelect(index, 'size', sizeOptions, 'Ölçü'));
            }

            if (metaRow.children.length > 0) {
                item.appendChild(metaRow);
            }

            preview.appendChild(item);
        });
    };

    // Photos no longer in the list no longer need their choices.
    const prune = () => {
        const count = input.files.length;
        meta = meta.slice(0, count);
    };

    input.addEventListener('change', () => {
        prune();
        render();
    });

    // The variant pickers change which options each photo offers.
    document.querySelectorAll('[data-sf-color-check], [data-sf-size-check]').forEach((check) => {
        check.addEventListener('change', render);
    });
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
            meta = [];
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

    paint(Number(input?.value || 0));
});

/* A review needs an explicit rating. */
document.querySelectorAll('.sf-review-form').forEach((form) => {
    form.addEventListener('submit', (e) => {
        const input = form.querySelector('[data-sf-rate-input]');
        if (input && Number(input.value) < 1) {
            e.preventDefault();
            alert('Zəhmət olmasa reytinq seçin.');
        }
    });
});

/* Buyer <-> seller chat modal on the product page. */
const listingChat = document.querySelector('[data-sf-lchat]');

if (listingChat) {
    const listEl = listingChat.querySelector('[data-sf-lchat-list]');
    const messagesEl = listingChat.querySelector('[data-sf-lchat-messages]');
    const headNameEl = listingChat.querySelector('[data-sf-lchat-thread-name]');
    const drawerEl = listingChat.querySelector('[data-sf-lchat-drawer]');
    const productCardEl = listingChat.querySelector('[data-sf-lchat-product]');
    const prodTitleEl = listingChat.querySelector('[data-sf-lchat-product-title]');
    const prodSubEl = listingChat.querySelector('[data-sf-lchat-product-sub]');
    const prodImgEl = listingChat.querySelector('[data-sf-lchat-product-img]');
    const form = listingChat.querySelector('[data-sf-lchat-form]');
    const input = form?.querySelector('input[name="body"]') || form?.querySelector('textarea');
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
        if (open) {
            if (drawerEl) drawerEl.hidden = true;
            input?.focus();
        }
    };

    const formatTime = (value) => {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
    };

    const getInitials = (name) => {
        if (!name) return 'U';
        const parts = name.trim().split(/\s+/);
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return name.slice(0, 2).toUpperCase();
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

        if (!message.mine) {
            const avatar = document.createElement('div');
            avatar.className = 'sf-lchat__avatar';
            const initials = document.createElement('span');
            initials.textContent = getInitials(headNameEl?.textContent || 'U');
            avatar.appendChild(initials);
            row.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.className = 'sf-lchat__bubble';

        const head = document.createElement('div');
        head.className = 'sf-lchat__bubble-head';

        const author = document.createElement('span');
        author.className = 'sf-lchat__bubble-author';
        author.textContent = message.mine ? 'You' : (headNameEl?.textContent || 'İstifadəçi');

        const meta = document.createElement('div');
        meta.className = 'sf-lchat__bubble-meta';

        if (message.mine) {
            const check = document.createElement('i');
            check.className = `sf-lchat__check fa-solid ${message.is_read ? 'fa-check-double is-read' : 'fa-check'}`;
            meta.appendChild(check);
        }

        const time = document.createElement('span');
        time.className = 'sf-lchat__bubble-time';
        time.textContent = formatTime(message.created_at);
        meta.appendChild(time);

        head.append(author, meta);

        const body = document.createElement('div');
        body.className = 'sf-lchat__bubble-text';
        body.textContent = message.body;

        bubble.append(head, body);
        row.append(bubble);

        messagesEl.append(row);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    const renderThread = (thread) => {
        activeId = thread.conversation?.id ?? null;
        const otherName = thread.conversation?.other?.name ?? 'Satıcı';
        if (headNameEl) headNameEl.textContent = otherName;

        const prod = thread.conversation?.product;
        if (productCardEl) {
            if (prod && (prod.title || prod.image)) {
                if (prodTitleEl) prodTitleEl.textContent = prod.title || 'Məhsul';
                if (prodSubEl) prodSubEl.textContent = 'Elan kodu: #' + prod.id;
                if (prodImgEl && prod.image) {
                    prodImgEl.src = prod.image;
                    prodImgEl.hidden = false;
                }
                productCardEl.hidden = false;
            } else {
                productCardEl.hidden = true;
            }
        }

        messagesEl.textContent = '';

        if (!thread.messages?.length) {
            messagesEl.append(empty('Hələ mesaj yoxdur. Söhbətə ilk siz başlayın! 👋'));
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
            if (drawerEl) drawerEl.hidden = true;
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

    listingChat.querySelectorAll('[data-sf-lchat-toggle-list]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (drawerEl) drawerEl.hidden = !drawerEl.hidden;
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !listingChat.hidden) {
            if (drawerEl && !drawerEl.hidden) {
                drawerEl.hidden = true;
            } else {
                setOpen(false);
            }
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

        const body = input?.value.trim();
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
            input.value = '';
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
