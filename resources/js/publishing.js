const t = text => window.adminTranslations?.[text] || text;
import { parseChapters, chapterPreview } from './chapter-parser.js';
function waitForUploads(form) {
    return new Promise(resolve => {
        const observer = new MutationObserver(check);
        function check() {
            if (form.querySelector('[data-upload-failed="true"]')) { observer.disconnect(); resolve(false); }
            else if (!form.querySelector('[data-upload-pending="true"]')) { observer.disconnect(); resolve(true); }
        }
        observer.observe(form, { subtree: true, attributes: true, attributeFilter: ['data-upload-pending', 'data-upload-failed'] });
        check();
    });
}
export function initPublishing() {
    document.querySelectorAll('.publishing-form').forEach(form => {
        const title = form.querySelector('[name="title"], [name="name"]');
        const description = form.querySelector('[name="excerpt"], [name="description"]');
        const seoTitle = form.querySelector('[name="seo_title"]');
        const seoDescription = form.querySelector('[name="seo_description"]');
        const slug = form.querySelector('[name="slug"]');
        if (form.hasAttribute('data-auto-slug') && title && slug) {
            const slugify = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[đĐ]/g, 'd').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            let automatic = !slug.value || slug.value === slugify(title.value);
            const updateSlug = () => {
                if (automatic) slug.value = slugify(title.value) + (form.querySelector('[name="compose_mode"]:checked')?.value === 'normal' && form.dataset.author ? '/' + form.dataset.author : '');
            };
            title.addEventListener('input', updateSlug);
            form.querySelectorAll('[name="compose_mode"]').forEach(mode => mode.addEventListener('change', updateSlug));
            slug.addEventListener('input', () => { automatic = !slug.value.trim(); updateSlug(); });
            updateSlug();
        }
        let queued = false;
        const showSaveMessage = (text, failed = false) => {
            const message = form.querySelector('[data-save-message]');
            if (message) { message.hidden = false; message.className = `alert alert-${failed ? 'danger' : 'info'} mt-3`; message.textContent = t(text); }
        };
        form.addEventListener('submit', async event => {
            if (queued) { event.preventDefault(); event.stopImmediatePropagation(); return; }
            const submitter = event.submitter;
            const status = form.querySelector('[name="status"]');
            if (submitter?.dataset.saveStatus && status) status.value = submitter.dataset.saveStatus;
            if (form.querySelector('[data-upload-failed="true"]')) {
                event.preventDefault(); event.stopImmediatePropagation();
                showSaveMessage('Image upload failed. Please select the image again before saving.', true);
                return;
            }
            if (!form.querySelector('[data-upload-pending="true"]')) return;
            event.preventDefault(); event.stopImmediatePropagation(); queued = true;
            const buttons = [...form.querySelectorAll('[data-save], [data-save-draft]')];
            buttons.forEach(button => { button.disabled = true; });
            form.setAttribute('aria-busy', 'true');
            showSaveMessage('Uploading images. Your content will be saved automatically when the uploads finish.');
            const uploaded = await waitForUploads(form);
            queued = false; buttons.forEach(button => { button.disabled = false; }); form.removeAttribute('aria-busy');
            if (!uploaded) { showSaveMessage('Image upload failed. Please select the image again before saving.', true); return; }
            const message = form.querySelector('[data-save-message]'); if (message) message.hidden = true;
            // Preserve the selected publish/draft action when resuming the form.
            if (submitter) form.requestSubmit(submitter); else form.requestSubmit();
        }, true);
        form.addEventListener('invalid', event => {
            let section = event.target.closest('details');
            while (section) { section.open = true; section = section.parentElement.closest('details'); }
        }, true);
        const refresh = () => {
            if (seoTitle) seoTitle.placeholder = title?.value.trim() || t('Uses the title when left blank');
            if (seoDescription) seoDescription.placeholder = description?.value.trim() || t('Uses the description when left blank');
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
            picker.querySelector('[data-choice-summary]').textContent = `${picker.querySelectorAll('input:checked').length} ${t('selected')}`;
        };
        search.addEventListener('input', refresh);
        search.addEventListener('keydown', event => {
            if (event.key === 'Enter' && !event.isComposing) { event.preventDefault(); event.stopPropagation(); }
        });
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
        input?.addEventListener('keydown', event => { if (event.key === 'Enter' && !event.isComposing) { event.preventDefault(); event.stopPropagation(); addTag(); } });
        refresh();
    });

    document.querySelectorAll('[data-image-picker]').forEach(picker => {
        const input = picker.querySelector('[data-image-input]');
        const image = picker.querySelector('[data-image-thumbnail]');
        const preview = picker.querySelector('[data-image-preview]');
        const reset = picker.querySelector('[data-image-reset]');
        const error = picker.querySelector('[data-image-error]');
        let objectUrl, uploadRevision = 0;
        input.addEventListener('change', async () => {
            const currentUpload = ++uploadRevision;
            delete picker.dataset.uploadFailed;
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            let file = input.files[0]; error.hidden = true;
            if (file && (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type) || file.size > 5 * 1024 * 1024)) {
                error.textContent = t('Choose a JPG, PNG, WebP or GIF image up to 5 MB.'); error.hidden = false; input.value = ''; file = null; picker.dataset.uploadFailed = 'true';
            }
            const src = file ? (objectUrl = URL.createObjectURL(file)) : image.dataset.currentSrc;
            if (src) image.src = src; else image.removeAttribute('src');
            preview.hidden = !src; reset.hidden = !file;
            picker.querySelector('[data-image-caption]').textContent = file ? `${file.name} · ${t('Selected image')}` : t('Current image');
            if (!picker.hasAttribute('data-immediate-upload')) return;
            const path = picker.querySelector('[name="image_path"]'); path.value = '';
            if (!file) { delete picker.dataset.uploadPending; return; }
            picker.dataset.uploadPending = 'true';
            picker.querySelector('[data-image-caption]').textContent = t('Uploading...');
            try {
                const data = new FormData(); data.append('upload', file);
                const response = await fetch('/admin/upload/featured', { method: 'POST', body: data, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                const result = await response.json();
                if (!response.ok || !result.path || !result.url) throw new Error(result.message || t('Image upload failed.'));
                if (currentUpload !== uploadRevision) return;
                path.value = result.path; image.src = result.url; input.value = '';
                picker.querySelector('[data-image-caption]').textContent = t('Uploaded');
            } catch (failure) {
                if (currentUpload !== uploadRevision) return;
                error.textContent = failure.message; error.hidden = false;
                picker.dataset.uploadFailed = 'true'; return;
            } finally {
                if (currentUpload === uploadRevision) delete picker.dataset.uploadPending;
            }
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
    let token = '', revision = 0, busy = false, saving = false;
    const importMode = () => form.querySelector('[name="compose_mode"]:checked').value === 'import';
    const pendingUpload = () => !!form.querySelector('[data-upload-pending="true"]');
    const invalidate = () => {
        revision++; token = ''; preview.hidden = true;
        save.disabled = busy;
        if (importMode()) {
            message.textContent = t('Save chapters directly, or use Analyze chapters for an optional preview.');
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
        save.textContent = importing ? t('Save chapters') : t('Save changes');
        form.querySelectorAll('[data-standard-required]').forEach(marker => { marker.hidden = false; });
        const titleInput = form.querySelector('[name="title"]');
        if (titleInput) { titleInput.required = true; titleInput.setAttribute('aria-required', 'true'); }
        hint.textContent = t('New content is published by default. Use Save draft to keep it private.');
        invalidate();
    };
    form.querySelectorAll('[name="compose_mode"]').forEach(input => input.addEventListener('change', toggle));
    ['input', 'change'].forEach(event => form.addEventListener(event, e => {
        if (e.target.matches('[data-choice-search],[data-new-tag]')) return;
        invalidate();
    }));
    const analyzeChapters = async (showPreview = true) => {
        if (busy || saving) return;
        if (pendingUpload()) { message.textContent = t('Wait for the image upload to finish, then analyze again.'); return; }
        invalidate();
        const currentRevision = revision;
        const data = new FormData(form);
        const source = form.querySelector('[name="manuscript"]');
        data.set('content', source.editor ? source.editor.getData() : source.value);
        data.delete('manuscript');
        data.set('description', data.get('excerpt') || '');
        data.delete('excerpt');
        busy = true; save.disabled = true; analyze.disabled = true; analyze.textContent = t('Analyzing...');
        message.textContent = showPreview ? t('Building chapter previews...') : t('Preparing chapters to save...');
        try {
            const result = parseChapters(data.get('content'));
            if (revision !== currentRevision || !importMode()) return;
            token = 'local'; preview.replaceChildren();
            const heading = document.createElement('div');
            heading.className = 'd-flex flex-wrap justify-content-between gap-2 align-items-start mb-3';
            const headingText = document.createElement('div');
            const eyebrow = document.createElement('span'); eyebrow.className = 'eyebrow'; eyebrow.textContent = t('Ready to review');
            const title = document.createElement('h2'); title.className = 'h4 mt-2';
            const selectedSeries = form.querySelector('[name="series_id"]');
            title.textContent = data.get('title')?.trim() || (selectedSeries?.value ? selectedSeries.selectedOptions[0].textContent : t('Chapter previews'));
            headingText.append(eyebrow, title);
            const count = document.createElement('span'); count.className = 'badge text-bg-light border'; count.textContent = `${result.chapters.length} ${t('Chapters')}`;
            heading.append(headingText, count); preview.append(heading);
            if (result.warnings.length) {
                const warnings = document.createElement('div'); warnings.className = 'alert alert-warning';
                const list = document.createElement('ul'); list.className = 'mb-0';
                result.warnings.forEach(warning => { const item = document.createElement('li'); item.textContent = warning; list.append(item); });
                warnings.append(list); preview.append(warnings);
            }
            const guidance = document.createElement('p'); guidance.className = 'small text-secondary';
            guidance.textContent = t('Open each chapter to review its complete content. To change the text, edit the manuscript and analyze again.'); preview.append(guidance);
            result.chapters.forEach((chapter, index) => {
                const details = document.createElement('details'), summary = document.createElement('summary'), content = document.createElement('div');
                details.className = 'chapter-preview'; details.open = index === 0;
                const number = document.createElement('span'); number.className = 'chapter-preview-number'; number.textContent = chapter.number;
                const label = document.createElement('span'); label.className = 'flex-grow-1'; label.textContent = chapter.title;
                const metadata = document.createElement('small'); metadata.className = 'd-block text-secondary fw-normal'; metadata.textContent = `${chapter.words} ${t('words')}`;
                label.append(metadata); summary.append(number, label);
                content.className = 'chapter-preview-content ck-content';
                content.append(chapterPreview(chapter.html)); details.append(summary, content); preview.append(details);
            });
            preview.hidden = !showPreview;
            message.className = 'small text-success mt-3'; message.textContent = t('Chapters are ready. Existing chapter numbers will be checked when saving.');
            if (showPreview) preview.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            message.className = 'alert alert-danger mt-3'; message.textContent = error.message;
        } finally { busy = false; save.disabled = false; analyze.disabled = false; analyze.textContent = t('Analyze chapters'); }
    };
    analyze.addEventListener('click', () => analyzeChapters());
    form.addEventListener('submit', async event => {
        if (event.defaultPrevented) return;
        if (pendingUpload()) { event.preventDefault(); return; }
        if (!importMode()) return;
        event.preventDefault();
        if (busy || saving) return;
        if (!token) await analyzeChapters(false);
        if (!token || !importMode() || pendingUpload()) return;
        const data = new FormData(form);
        const source = form.querySelector('[name="manuscript"]');
        data.set('content', source.editor ? source.editor.getData() : source.value);
        data.set('description', data.get('excerpt') || ''); data.delete('manuscript'); data.delete('_method');
        confirmation.action = '/admin/import/save'; confirmation.replaceChildren();
        for (const [name, value] of data) {
            if (value instanceof File) continue;
            const input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = value; confirmation.append(input);
        }
        saving = true; save.disabled = true;
        const draft = form.querySelector('[data-save-draft]'); if (draft) draft.disabled = true;
        save.textContent = t('Saving...'); confirmation.requestSubmit();
    });
    toggle();
}
