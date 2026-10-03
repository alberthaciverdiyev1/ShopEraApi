import './app.css';
import htmx from 'htmx.org';
import 'flowbite';

declare global {
    interface Window {
        htmx: typeof htmx;
        FoxAdmin: { toast: (type: string, message: string) => void };
    }
}

window.htmx = htmx;

/**
 * Toast notifications. The server raises `HX-Trigger: { "toast": {...} }`
 * after a successful mutation; we render it here and fade it out.
 */
function toast(type: string, message: string): void {
    const root = document.getElementById('toast-root');
    if (!root || !message) return;

    const tone: Record<string, string> = {
        success: 'bg-emerald-600',
        error: 'bg-rose-600',
        info: 'bg-gray-800',
    };

    const el = document.createElement('div');
    el.className = `pointer-events-auto rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg ${tone[type] ?? tone.info}`;
    el.textContent = message;
    el.style.opacity = '0';
    el.style.transform = 'translateY(6px)';
    root.appendChild(el);

    requestAnimationFrame(() => {
        el.style.transition = 'all .25s ease';
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    });

    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(6px)';
        setTimeout(() => el.remove(), 300);
    }, 3200);
}

window.FoxAdmin = { toast };

document.addEventListener('toast', (event) => {
    const detail = (event as CustomEvent<{ type: string; message: string }>).detail;
    if (detail) toast(detail.type, detail.message);
});

/** Clear the htmx modal host (close button, backdrop, Esc and on success). */
function closeModal(): void {
    const root = document.getElementById('modal-root');
    if (root) root.innerHTML = '';
}

document.body.addEventListener('modal:close', closeModal);
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeModal();
});

/** Sidebar: off-canvas drawer on mobile, collapsible rail on desktop. */
document.addEventListener('DOMContentLoaded', () => {
    const shell = document.getElementById('admin-shell');
    const toggle = document.getElementById('sidebar-toggle');
    if (!shell || !toggle) return;

    const isMobile = () => window.matchMedia('(max-width: 1023.98px)').matches;
    const closeDrawer = () => shell.classList.remove('sidebar-open');

    // Desktop collapse state, remembered across page loads.
    if (!isMobile() && localStorage.getItem('admin:sidebar') === 'collapsed') {
        shell.classList.add('sidebar-collapsed');
    }

    toggle.addEventListener('click', () => {
        if (isMobile()) {
            shell.classList.toggle('sidebar-open');
            return;
        }
        shell.classList.toggle('sidebar-collapsed');
        localStorage.setItem('admin:sidebar', shell.classList.contains('sidebar-collapsed') ? 'collapsed' : 'open');
    });

    // Close the mobile drawer on backdrop click, Escape, or a nav item tap.
    document.querySelectorAll('[data-drawer-close]').forEach((el) => el.addEventListener('click', closeDrawer));
    document.querySelectorAll('aside.sidebar a').forEach((a) => a.addEventListener('click', () => { if (isMobile()) closeDrawer(); }));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDrawer(); });
    window.addEventListener('resize', () => { if (!isMobile()) closeDrawer(); });
});

/** Category tree: toggling a row shows/hides every descendant row. */
document.addEventListener('click', (event) => {
    const button = (event.target as HTMLElement).closest<HTMLElement>('[data-toggle-children]');
    if (!button) return;

    const id = button.getAttribute('data-toggle-children');
    if (!id) return;

    document.querySelectorAll(`[data-chain~="${id}"]`).forEach((row) => row.classList.toggle('hidden'));
    button.querySelector('.tree-chevron')?.classList.toggle('rotate-90');
});

/** Language tabs (AZ/EN/RU/TR) inside forms: one locale panel at a time. */
document.addEventListener('click', (event) => {
    const tab = (event.target as HTMLElement).closest<HTMLElement>('[data-lang-tab]');
    if (!tab) return;

    const group = tab.closest('[data-lang-tabs]');
    const key = tab.getAttribute('data-lang-tab');
    if (!group || !key) return;

    group.querySelectorAll<HTMLElement>('[data-lang-tab]').forEach((el) => {
        el.classList.remove('bg-white', 'text-brand-600', 'shadow-sm', 'dark:bg-gray-800');
        el.classList.add('text-gray-500');
    });
    tab.classList.add('bg-white', 'text-brand-600', 'shadow-sm', 'dark:bg-gray-800');
    tab.classList.remove('text-gray-500');

    group.querySelectorAll<HTMLElement>('[data-lang-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', panel.getAttribute('data-lang-panel') !== key);
    });
});

/**
 * Preview chosen files. The input points at a container via data-preview;
 * every selected file becomes a thumbnail (image) or a small player (video).
 */
document.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || input.type !== 'file' || !input.dataset.preview) return;

    const container = document.getElementById(input.dataset.preview);
    if (!container) return;

    container.innerHTML = '';
    Array.from(input.files ?? []).forEach((file) => {
        const isVideo = file.type.startsWith('video');
        const el = document.createElement(isVideo ? 'video' : 'img');
        el.setAttribute('src', URL.createObjectURL(file));
        el.className = 'h-20 w-20 rounded-lg object-cover ring-1 ring-gray-200';
        if (isVideo) {
            (el as HTMLVideoElement).muted = true;
        }
        container.appendChild(el);
    });
});

/** Poll an open chat thread. */
function refreshChat(): void {
    const thread = document.getElementById('chat-thread');
    if (thread) htmx.trigger(thread, 'chat:refresh');
}

setInterval(() => {
    if (document.getElementById('chat-thread')) refreshChat();
}, 10000);

/** Show/hide fields based on another field's value (e.g. popup media type). */
document.addEventListener('change', (event) => {
    const el = event.target;
    if (!(el instanceof HTMLInputElement || el instanceof HTMLSelectElement || el instanceof HTMLTextAreaElement)) return;

    const name = el.getAttribute('name');
    if (!name) return;

    document.querySelectorAll<HTMLElement>(`[data-show-field="${name}"]`).forEach((panel) => {
        panel.classList.toggle('hidden', el.value !== panel.getAttribute('data-show-value'));
    });
});

/* ---------- Loading indicators (buttons + global spinner) ---------- */

let activeRequests = 0;

function globalSpinner(): HTMLElement | null {
    return document.getElementById('global-spinner');
}

function updateGlobalSpinner(): void {
    globalSpinner()?.classList.toggle('is-active', activeRequests > 0);
}

/** Resolve the element that should carry the spinner (button, or a form's submit button). */
function spinnerTarget(el: EventTarget | null): HTMLElement | null {
    if (!(el instanceof HTMLElement)) return null;

    if (el instanceof HTMLFormElement) {
        return el.querySelector<HTMLElement>('button[type="submit"], button:not([type])') ?? el;
    }

    return el;
}

function startLoading(el: EventTarget | null): void {
    const target = spinnerTarget(el);
    if (!target) return;

    activeRequests++;
    updateGlobalSpinner();

    const isButton =
        target instanceof HTMLButtonElement ||
        (target instanceof HTMLInputElement && target.type === 'submit') ||
        target.tagName === 'BUTTON';

    if (!isButton || target.dataset.loading === '1') return;

    target.dataset.loading = '1';
    target.dataset.originalHtml = target.innerHTML;
    target.classList.add('is-loading');
    if (target instanceof HTMLButtonElement || target instanceof HTMLInputElement) {
        target.disabled = true;
    }
    target.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span>' + (target.dataset.originalHtml ?? '');
}

function stopLoading(el: EventTarget | null): void {
    if (activeRequests > 0) activeRequests--;
    updateGlobalSpinner();

    const target = spinnerTarget(el);
    if (!target || target.dataset.loading !== '1') return;

    target.dataset.loading = '0';
    target.classList.remove('is-loading');
    if (target instanceof HTMLButtonElement || target instanceof HTMLInputElement) {
        target.disabled = false;
    }
    if (target.dataset.originalHtml !== undefined) {
        target.innerHTML = target.dataset.originalHtml;
        delete target.dataset.originalHtml;
    }
}

document.body.addEventListener('htmx:beforeRequest', (event: Event) => {
    startLoading((event as CustomEvent).detail?.elt ?? null);
});
document.body.addEventListener('htmx:afterRequest', (event: Event) => {
    stopLoading((event as CustomEvent).detail?.elt ?? null);
});
document.body.addEventListener('htmx:responseError', (event: Event) => {
    stopLoading((event as CustomEvent).detail?.elt ?? null);
});
document.body.addEventListener('htmx:sendError', (event: Event) => {
    stopLoading((event as CustomEvent).detail?.elt ?? null);
});

/* Plain (non-htmx) form submissions: show spinner until the page navigates. */
document.addEventListener('submit', (event) => {
    const form = event.target as HTMLFormElement | null;
    if (!form || form.hasAttribute('hx-post') || form.hasAttribute('hx-put') || form.hasAttribute('hx-delete') || form.hasAttribute('hx-get')) {
        return;
    }

    const button = form.querySelector<HTMLButtonElement>('button[type="submit"], button:not([type])');
    startLoading(button ?? form);
});
