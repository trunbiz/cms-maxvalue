const t = text => window.adminTranslations?.[text] || text;
export function initCopyLinks() {
    document.querySelectorAll('[data-copy-link]').forEach(button => button.addEventListener('click', async () => {
        const originalTitle = button.title;
        const status = button.parentElement.querySelector('[data-copy-status]');
        const value = button.dataset.copyLink;
        try {
            if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(value);
            else {
                const input = document.createElement('textarea'); input.value = value;
                input.style.position = 'fixed'; input.style.opacity = '0'; document.body.append(input); input.select();
                const copied = document.execCommand('copy'); input.remove(); button.focus();
                if (!copied) throw new Error('Clipboard unavailable');
            }
            button.title = t('Link copied!');
            button.classList.add('text-success');
            if (status) status.textContent = t('Link copied!');
            setTimeout(() => { button.title = originalTitle; button.classList.remove('text-success'); if (status) status.textContent = ''; }, 2000);
        } catch { window.prompt(t('Copy this link:'), value); }
    }));
}
