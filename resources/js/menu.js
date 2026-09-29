import Sortable from 'sortablejs';
export function initMenu() {
    const editor = document.querySelector('[data-menu-editor]');
    const root = editor.querySelector('[data-menu-root]');
    let nextId = -1;
    const initial = JSON.parse(editor.querySelector('[data-menu-data]').textContent);
    const sortable = el => new Sortable(el, { group: 'menu', animation: 150, handle: '.drag-handle', fallbackOnBody: true, swapThreshold: 0.65 });
    const makeItem = data => {
        const li = document.createElement('li'); li.dataset.id = data.id; li.menuData = data;
        const row = document.createElement('div'); row.className = 'd-flex align-items-center gap-2';
        const handle = document.createElement('span'); handle.className = 'drag-handle'; handle.textContent = '⠿'; handle.title = 'Kéo để sắp xếp';
        const input = document.createElement('input'); input.className = 'form-control form-control-sm'; input.value = data.label; input.setAttribute('aria-label','Nhãn menu'); input.addEventListener('input', () => { data.label = input.value; });
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-sm btn-outline-danger'; remove.textContent = 'Bỏ'; remove.addEventListener('click', () => li.remove());
        row.append(handle, input, remove); li.append(row);
        const children = document.createElement('ul'); children.className = 'menu-sortable list-unstyled'; li.append(children); sortable(children);
        return li;
    };
    const nodes = new Map(initial.map(data => [data.id, makeItem(data)]));
    initial.forEach(item => { const parent = nodes.get(item.parent_id); (parent ? parent.querySelector('ul') : root).append(nodes.get(item.id)); }); sortable(root);
    editor.querySelector('[data-menu-add]').addEventListener('click', () => {
        const label = editor.querySelector('[data-menu-label]');
        if (!label.value.trim()) { label.focus(); return; }
        const [type, target] = editor.querySelector('[data-menu-target]').value.split(':');
        root.append(makeItem({ id: nextId--, label: label.value.trim(), type, target_id: target ? Number(target) : null, url: editor.querySelector('[data-menu-url]').value || null })); label.value = '';
    });
    editor.querySelector('[data-menu-save]').addEventListener('click', async event => {
        const items = []; const collect = (list, parent = null) => [...list.children].forEach(li => { items.push({ ...li.menuData, parent_id: parent }); collect(li.querySelector('ul'), li.menuData.id); }); collect(root);
        const status = editor.querySelector('[data-menu-status]'); event.target.disabled = true;
        try {
            const response = await fetch(editor.dataset.url, { method: 'PUT', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ items }) });
            const result = await response.json(); if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message);
            location.reload();
        } catch(error) { status.textContent = error.message || 'Không thể lưu menu.'; } finally { event.target.disabled = false; }
    });
}
