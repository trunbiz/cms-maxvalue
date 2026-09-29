export function initCopyLinks() {
    document.querySelectorAll('[data-copy-link]').forEach(button => button.addEventListener('click', async () => {
        const original = button.textContent;
        const value = button.dataset.copyLink;
        try {
            if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(value);
            else {
                const input = document.createElement('textarea'); input.value = value;
                input.style.position = 'fixed'; input.style.opacity = '0'; document.body.append(input); input.select();
                const copied = document.execCommand('copy'); input.remove(); button.focus();
                if (!copied) throw new Error('Clipboard unavailable');
            }
            button.textContent = 'Link copied!'; button.setAttribute('aria-live', 'polite');
            setTimeout(() => { button.textContent = original; }, 2000);
        } catch { window.prompt('Copy this link:', value); }
    }));
}
