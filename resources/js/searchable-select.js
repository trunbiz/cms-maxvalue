const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[đĐ]/g, 'd').toLowerCase();
const t = text => window.adminTranslations?.[text] || text;

export function initSearchableSelects() {
    let closeActive;
    document.querySelectorAll('[data-searchable-select]').forEach((select, index) => {
        if (select.dataset.searchReady) return;
        select.dataset.searchReady = 'true';
        const wrapper = document.createElement('div');
        wrapper.className = 'admin-searchable-filter';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'form-select searchable-select-trigger';
        trigger.setAttribute('aria-label', select.getAttribute('aria-label') || select.dataset.searchLabel);
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.disabled = select.disabled;
        const panel = document.createElement('div');
        panel.className = 'searchable-select-panel';
        panel.hidden = true;
        const search = document.createElement('input');
        search.type = 'search';
        search.className = 'form-control searchable-select-search';
        search.placeholder = select.dataset.searchLabel;
        search.setAttribute('aria-label', select.dataset.searchLabel);
        search.setAttribute('role', 'combobox');
        search.setAttribute('aria-autocomplete', 'list');
        search.setAttribute('aria-expanded', 'false');
        const list = document.createElement('div');
        list.className = 'searchable-select-options';
        list.id = `searchable-select-${select.name}-${index}`;
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', trigger.getAttribute('aria-label'));
        trigger.setAttribute('aria-controls', list.id);
        search.setAttribute('aria-controls', list.id);
        const empty = document.createElement('p');
        empty.className = 'searchable-select-empty';
        empty.textContent = t('No matches found.');
        empty.setAttribute('role', 'status');
        panel.append(search, list, empty);
        select.before(wrapper);
        wrapper.append(select, trigger, panel);
        select.hidden = true;
        let active = -1;
        let choices = [];
        const setActive = position => {
            active = position;
            [...list.children].forEach((row, key) => row.classList.toggle('is-active', key === active));
            const row = list.children[active];
            if (row) {
                search.setAttribute('aria-activedescendant', row.id);
                row.scrollIntoView?.({ block: 'nearest' });
            } else search.removeAttribute('aria-activedescendant');
        };
        const close = (focus = false) => {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            search.setAttribute('aria-expanded', 'false');
            search.removeAttribute('aria-activedescendant');
            if (focus) trigger.focus();
        };
        const render = () => {
            const term = normalize(search.value.trim());
            choices = [...select.options].filter(option => !option.disabled && !option.hidden && normalize(option.textContent).includes(term));
            list.replaceChildren();
            choices.forEach((option, key) => {
                const row = document.createElement('div');
                row.className = 'searchable-select-option';
                row.id = `${list.id}-option-${key}`;
                row.setAttribute('role', 'option');
                row.setAttribute('aria-selected', String(option.selected));
                row.textContent = option.textContent;
                row.addEventListener('click', () => choose(key));
                list.append(row);
            });
            empty.hidden = choices.length > 0;
            const selected = choices.findIndex(option => option.selected);
            setActive(selected >= 0 ? selected : choices.length ? 0 : -1);
        };
        const refresh = () => {
            trigger.textContent = select.options[select.selectedIndex]?.textContent || select.options[0]?.textContent || '';
            render();
        };
        const choose = position => {
            if (!choices[position]) return;
            select.value = choices[position].value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close(true);
        };
        const open = () => {
            closeActive?.();
            closeActive = close;
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            search.setAttribute('aria-expanded', 'true');
            search.value = '';
            render();
            search.focus();
        };
        trigger.addEventListener('click', () => panel.hidden ? open() : close());
        trigger.addEventListener('keydown', event => {
            if (['ArrowDown', 'ArrowUp'].includes(event.key)) { event.preventDefault(); open(); }
        });
        search.addEventListener('input', render);
        search.addEventListener('keydown', event => {
            if (event.isComposing) return;
            if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
                event.preventDefault();
                if (choices.length) setActive((active + (event.key === 'ArrowDown' ? 1 : -1) + choices.length) % choices.length);
            }
            if (event.key === 'Enter') { event.preventDefault(); choose(active); }
            if (event.key === 'Escape') { event.preventDefault(); close(true); }
        });
        wrapper.addEventListener('focusout', event => { if (!wrapper.contains(event.relatedTarget)) close(); });
        document.addEventListener('pointerdown', event => { if (!wrapper.contains(event.target)) close(); });
        select.addEventListener('change', refresh);
        select.form?.addEventListener('reset', () => { close(); window.setTimeout(refresh, 0); });
        refresh();
    });
}
