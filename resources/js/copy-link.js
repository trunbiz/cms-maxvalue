const t = text => window.adminTranslations?.[text] || text;
let noticeTimer;
export function showCopyNotice() {
    let notice = document.querySelector('[data-copy-notice]');
    if (!notice) {
        notice = document.createElement('div'); notice.dataset.copyNotice = '';
        notice.className = 'position-fixed bottom-0 end-0 m-3 px-3 py-2 bg-success text-white rounded shadow';
        notice.style.zIndex = '1090'; notice.setAttribute('role', 'status');
        document.body.append(notice);
    }
    notice.textContent = t('Link copied!'); notice.hidden = false;
    clearTimeout(noticeTimer); noticeTimer = setTimeout(() => { notice.hidden = true; }, 1500);
}
export async function copyLink(value) {
    try {
        if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(value);
        else {
            const input = document.createElement('textarea'); input.value = value;
            input.style.position = 'fixed'; input.style.opacity = '0'; document.body.append(input); input.select();
            const copied = document.execCommand('copy'); input.remove();
            if (!copied) throw new Error('Clipboard unavailable');
        }
        showCopyNotice();
        return true;
    } catch { window.prompt(t('Copy this link:'), value); return false; }
}
export function initCopyLinks() {
    document.querySelectorAll('[data-copy-link]').forEach(button => button.addEventListener('click', async () => {
        const originalTitle = button.title;
        const status = button.parentElement.querySelector('[data-copy-status]');
        const value = button.dataset.copyLink;
        if (await copyLink(value)) {
            button.focus();
            button.title = t('Link copied!');
            button.classList.add('text-success');
            if (status) status.textContent = t('Link copied!');
            setTimeout(() => { button.title = originalTitle; button.classList.remove('text-success'); if (status) status.textContent = ''; }, 2000);
        }
    }));
}
