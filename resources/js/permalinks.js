export function initPermalinks() {
    document.querySelectorAll('[data-permalink-settings]').forEach(section => {
        const presets = JSON.parse(section.dataset.permalinkPresets);
        const tokens = JSON.parse(section.dataset.permalinkTokens);
        const input = section.querySelector('[data-permalink-input]');
        const preview = section.querySelector('[data-permalink-preview]');
        const choices = [...section.querySelectorAll('[name="permalink_structure"]')];
        let custom = section.dataset.customStructure || presets.name;
        let active = choices.find(choice => choice.checked).value;
        const showPreview = () => {
            const path = input.value.replace(/%[a-z_]+%/g, token => tokens[token] ?? token);
            preview.textContent = section.dataset.baseUrl.replace(/\/$/, '') + path;
            section.querySelectorAll('[data-permalink-token]').forEach(button => {
                button.disabled = active === 'custom' && input.value.includes(button.dataset.permalinkToken);
            });
        };
        const update = () => {
            if (active === 'custom') custom = input.value;
            active = choices.find(choice => choice.checked).value;
            input.disabled = active !== 'custom';
            input.value = active === 'custom' ? custom : presets[active];
            showPreview();
        };
        choices.forEach(choice => choice.addEventListener('change', update));
        input.addEventListener('input', () => { custom = input.value; showPreview(); });
        section.querySelectorAll('[data-permalink-token]').forEach(button => button.addEventListener('click', () => {
            if (active !== 'custom') {
                choices.find(choice => choice.value === 'custom').checked = true;
                update();
            }
            const token = button.dataset.permalinkToken;
            if (input.value.includes(token)) { input.focus(); return; }
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? start;
            const before = input.value.slice(0, start), after = input.value.slice(end);
            const segment = (before.endsWith('/') ? '' : '/') + token + (after.startsWith('/') ? '' : '/');
            input.setRangeText(segment, start, end, 'end');
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        }));
        update();
    });
}
