export function initPublishing() {
    document.querySelectorAll('.publishing-form').forEach(form => {
        const title = form.querySelector('[name="title"], [name="name"]');
        const description = form.querySelector('[name="excerpt"], [name="description"]');
        const seoTitle = form.querySelector('[name="seo_title"]');
        const seoDescription = form.querySelector('[name="seo_description"]');
        const refresh = () => {
            if (seoTitle) seoTitle.placeholder = title?.value.trim() || 'Uses the title when left blank';
            if (seoDescription) seoDescription.placeholder = description?.value.trim() || 'Uses the description when left blank';
        };
        title?.addEventListener('input', refresh);
        description?.addEventListener('input', refresh);
        refresh();
    });

    document.querySelectorAll('[data-choice-picker]').forEach(picker => {
        const search = picker.querySelector('[data-choice-search]');
        const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        const refresh = () => {
            const rows = [...picker.querySelectorAll('.choice-option')];
            rows.forEach(row => { row.hidden = !normalize(row.textContent).includes(normalize(search.value.trim())); });
            picker.querySelector('[data-choice-empty]').hidden = rows.some(row => !row.hidden);
            picker.querySelector('[data-choice-summary]').textContent = `${picker.querySelectorAll('input:checked').length} selected`;
        };
        search.addEventListener('input', refresh);
        picker.addEventListener('change', refresh);
        const input = picker.querySelector('[data-new-tag]');
        const addTag = () => {
            const name = input.value.trim();
            if (!name) return;
            let row = [...picker.querySelectorAll('.choice-option')].find(row => normalize(row.textContent.trim()) === normalize(name));
            if (!row) {
                row = document.createElement('label'); row.className = 'choice-option';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox'; checkbox.name = 'tags[]'; checkbox.value = `new:${name}`; checkbox.className = 'form-check-input';
                const text = document.createElement('span'); text.textContent = name;
                row.append(checkbox, text); picker.querySelector('[data-choice-list]').append(row);
            }
            const checkbox = row.querySelector('input'); checkbox.checked = true;
            input.value = ''; search.value = ''; checkbox.dispatchEvent(new Event('change', { bubbles: true })); refresh();
        };
        picker.querySelector('[data-add-tag]')?.addEventListener('click', addTag);
        input?.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); addTag(); } });
        refresh();
    });

    document.querySelectorAll('[data-image-picker]').forEach(picker => {
        const input = picker.querySelector('[data-image-input]');
        const image = picker.querySelector('[data-image-thumbnail]');
        const preview = picker.querySelector('[data-image-preview]');
        const reset = picker.querySelector('[data-image-reset]');
        const error = picker.querySelector('[data-image-error]');
        let objectUrl;
        input.addEventListener('change', () => {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            let file = input.files[0]; error.hidden = true;
            if (file && (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type) || file.size > 5 * 1024 * 1024)) {
                error.textContent = 'Choose a JPG, PNG, WebP or GIF image up to 5 MB.'; error.hidden = false; input.value = ''; file = null;
            }
            const src = file ? (objectUrl = URL.createObjectURL(file)) : image.dataset.currentSrc;
            if (src) image.src = src; else image.removeAttribute('src');
            preview.hidden = !src; reset.hidden = !file;
            picker.querySelector('[data-image-caption]').textContent = file ? `${file.name} · Selected image` : 'Current image';
        });
        reset.addEventListener('click', () => { input.value = ''; input.dispatchEvent(new Event('change', { bubbles: true })); });
    });

    const form = document.querySelector('[data-composer]');
    if (!form) return;
    const standard = form.querySelector('[data-standard-content]');
    const manuscript = form.querySelector('[data-import-content]');
    const importMetadata = form.querySelector('[data-import-metadata]');
    const publicationDate = form.querySelector('[data-field="published_at"]');
    const preview = form.querySelector('[data-chapter-preview]');
    const message = form.querySelector('[data-import-message]');
    const analyze = form.querySelector('[data-analyze]');
    const save = form.querySelector('[data-save]');
    const hint = form.querySelector('[data-save-hint]');
    const confirmation = document.querySelector('[data-import-confirm]');
    let token = '', revision = 0, busy = false;
    const importMode = () => form.querySelector('[name="compose_mode"]:checked').value === 'import';
    const pendingUpload = () => !!form.querySelector('[data-upload-pending="true"]');
    const invalidate = () => {
        revision++; token = ''; preview.hidden = true;
        save.disabled = importMode();
        if (importMode()) {
            message.textContent = 'Analyze the manuscript to preview chapters before saving.';
            message.className = 'small text-secondary mt-3';
        }
    };
    const toggle = () => {
        const importing = importMode();
        [[standard, importing], [manuscript, !importing], [form.querySelector('[data-import-image]'), !importing], [importMetadata, !importing], [publicationDate, importing]].forEach(([section, hidden]) => {
            if (!section) return;
            section.hidden = hidden;
            section.querySelectorAll('input,textarea,select').forEach(input => { input.disabled = hidden; });
        });
        save.textContent = importing ? 'Save chapters' : 'Save changes';
        hint.textContent = importing ? 'Analyze, review the chapters, then save. Choose Published to publish them.' : 'New content is public by default. Choose Draft to keep it private.';
        invalidate();
    };
    form.querySelectorAll('[name="compose_mode"]').forEach(input => input.addEventListener('change', toggle));
    ['input', 'change'].forEach(event => form.addEventListener(event, e => {
        if (e.target.matches('[data-choice-search],[data-new-tag]')) return;
        invalidate();
    }));
    analyze.addEventListener('click', async () => {
        if (busy) return;
        if (pendingUpload()) { message.textContent = 'Wait for the image upload to finish, then analyze again.'; return; }
        invalidate();
        const currentRevision = revision;
        const data = new FormData(form);
        const source = form.querySelector('[name="manuscript"]');
        data.set('content', source.editor ? source.editor.getData() : source.value);
        data.delete('manuscript');
        data.set('description', data.get('excerpt') || '');
        data.delete('excerpt');
        busy = true; analyze.disabled = true; analyze.textContent = 'Analyzing...';
        message.textContent = 'Building chapter previews...';
        try {
            const response = await fetch('/admin/import/preview', { method: 'POST', body: data, headers: { Accept: 'application/json' } });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Unable to analyze. Check your connection or sign in again.');
            if (revision !== currentRevision) { message.textContent = 'The manuscript or settings changed. Analyze again to refresh the preview.'; return; }
            token = result.token;
            preview.innerHTML = result.html; // Server-rendered Blade fragment; chapter HTML is sanitized on the server.
            preview.hidden = false; save.disabled = false;
            message.className = 'small text-success mt-3'; message.textContent = 'Chapters are ready below. Review them, then save.';
            preview.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            message.className = 'alert alert-danger mt-3'; message.textContent = error.message;
        } finally { busy = false; analyze.disabled = false; analyze.textContent = 'Analyze chapters'; }
    });
    form.addEventListener('submit', event => {
        if (pendingUpload()) { event.preventDefault(); return; }
        if (!importMode()) return;
        event.preventDefault();
        if (!token || busy) { message.textContent = 'Analyze and review the chapters before saving.'; return; }
        confirmation.elements.token.value = token; save.disabled = true; save.textContent = 'Saving...'; confirmation.requestSubmit();
    });
    toggle();
}
