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

/** Sidebar collapse state, remembered across page loads. */
document.addEventListener('DOMContentLoaded', () => {
    const shell = document.getElementById('admin-shell');
    const toggle = document.getElementById('sidebar-toggle');
    const collapsed = localStorage.getItem('admin:sidebar') === 'collapsed';

    if (shell && collapsed) shell.classList.add('sidebar-collapsed');

    toggle?.addEventListener('click', () => {
        if (!shell) return;
        shell.classList.toggle('sidebar-collapsed');
        localStorage.setItem('admin:sidebar', shell.classList.contains('sidebar-collapsed') ? 'collapsed' : 'open');
    });
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
