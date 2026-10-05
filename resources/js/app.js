import { Modal, Toast, Offcanvas } from 'bootstrap';

document.querySelectorAll('[data-auto-toast]').forEach(el => new Toast(el).show());
let pendingDelete;
document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    event.preventDefault(); pendingDelete = form;
    Modal.getOrCreateInstance(document.getElementById('deleteModal')).show();
}));
document.getElementById('confirmDelete')?.addEventListener('click', () => pendingDelete?.submit());
document.querySelectorAll('.submenu-toggle').forEach(button => button.addEventListener('click', () => {
    const expanded = button.getAttribute('aria-expanded') !== 'true';
    button.setAttribute('aria-expanded', String(expanded));
    button.parentElement.classList.toggle('submenu-open', expanded);
}));

if (document.querySelector('[data-editor]')) import('./editor-loader').then(({ initEditors }) => initEditors());
if (document.querySelector('[data-menu-editor]')) import('./menu').then(({ initMenu }) => initMenu());
if (document.querySelector('.admin-body')) import('./publishing').then(({ initPublishing }) => initPublishing());
if (document.querySelector('[data-copy-link]')) import('./copy-link').then(({ initCopyLinks }) => initCopyLinks());
if (document.querySelector('[data-permalink-settings]')) import('./permalinks').then(({ initPermalinks }) => initPermalinks());

const readStored = (key, fallback = {}) => { try { return JSON.parse(localStorage.getItem(key)) || fallback; } catch { return fallback; } };
const saveStored = (key, value) => { try { localStorage.setItem(key, JSON.stringify(value)); } catch {} };
const reader = document.querySelector('[data-reader]');
if (reader) {
    const settings = { size: 19, width: 70, font: 'serif', theme: 'light', ...readStored('reader.settings') };
    const apply = () => {
        const root = document.documentElement;
        root.dataset.theme = ['light', 'sepia', 'dark'].includes(settings.theme) ? settings.theme : 'light';
        root.style.setProperty('--reading-size', `${Math.min(24, Math.max(16, +settings.size || 19))}px`);
        root.style.setProperty('--reading-width', `${Math.min(90, Math.max(45, +settings.width || 70))}ch`);
        root.style.setProperty('--reading-font', settings.font === 'sans' ? '"Be Vietnam Pro",sans-serif' : '"Literata",serif');
        saveStored('reader.settings', settings);
    };
    document.querySelectorAll('[data-reader-setting]').forEach(input => {
        input.value = settings[input.dataset.readerSetting];
        input.addEventListener('input', () => { settings[input.dataset.readerSetting] = input.value; apply(); });
    });
    apply();
    const history = readStored('reader.history');
    history[reader.dataset.seriesId] = { url: location.pathname, title: reader.dataset.chapterTitle, at: Date.now() };
    const recent = Object.fromEntries(Object.entries(history).sort((a,b) => b[1].at - a[1].at).slice(0, 100));
    saveStored('reader.history', recent);
    document.addEventListener('keydown', event => {
        if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey || event.target.closest('input,textarea,select,[contenteditable="true"]')) return;
        const link = event.key === 'ArrowLeft' ? document.querySelector('[data-previous]') : event.key === 'ArrowRight' ? document.querySelector('[data-next]') : null;
        if (link) { event.preventDefault(); location.assign(link.href); }
    });

}
document.querySelectorAll('[data-continue-series]').forEach(link => {
    const item = readStored('reader.history')[link.dataset.continueSeries];
    if (typeof item?.url === 'string' && item.url.startsWith(link.dataset.seriesPrefix) && !item.url.includes('\\')) {
        link.href = item.url; link.classList.remove('d-none');
    }
});

const chapterSticky = document.querySelector('[data-chapter-sticky]');
if (chapterSticky) {
    chapterSticky.open = false;
    const revealCurrent = () => {
        const list = chapterSticky.querySelector('.chapter-sticky-list');
        const current = list.querySelector('[aria-current="page"]');
        if (chapterSticky.open && current) list.scrollTop = current.offsetTop - list.offsetTop - (list.clientHeight - current.offsetHeight) / 2;
    };
    chapterSticky.addEventListener('toggle', revealCurrent);
    revealCurrent();
    document.addEventListener('pointerdown', event => {
        if (chapterSticky.open && !chapterSticky.contains(event.target)) chapterSticky.open = false;
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && chapterSticky.open) {
            const hadFocus = chapterSticky.contains(document.activeElement);
            chapterSticky.open = false;
            if (hadFocus) chapterSticky.querySelector('summary').focus();
        }
    });
}

const bulkPosts = document.querySelector('[data-bulk-posts]');
if (bulkPosts) {
    const all = document.querySelector('[data-select-all]');
    const boxes = [...document.querySelectorAll('[data-select-post]')];
    const updateSelection = () => {
        const count = boxes.filter(box => box.checked).length;
        bulkPosts.querySelector('[data-bulk-actions]').hidden = count === 0;
        bulkPosts.querySelector('[data-selected-count]').textContent = `${count} selected`;
        all.checked = count > 0 && count === boxes.length;
        all.indeterminate = count > 0 && count < boxes.length;
    };
    all.addEventListener('change', () => { boxes.forEach(box => box.checked = all.checked); updateSelection(); });
    boxes.forEach(box => box.addEventListener('change', updateSelection));
    updateSelection();
}
