import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { initPublishing } from '../../resources/js/publishing.js';
import { initCopyLinks } from '../../resources/js/copy-link.js';

function setup(html) {
    const dom = new JSDOM(html, { url: 'http://localhost/admin/posts/create', pretendToBeVisual: true });
    for (const name of ['window', 'document', 'HTMLElement', 'HTMLTextAreaElement', 'HTMLInputElement', 'HTMLTemplateElement', 'Node', 'Element', 'DOMParser', 'MutationObserver', 'Event', 'CustomEvent', 'DOMRect', 'FormData', 'File', 'FileReader', 'Range', 'HTMLDivElement', 'HTMLSpanElement', 'HTMLButtonElement', 'HTMLImageElement', 'HTMLTableElement', 'HTMLTableRowElement', 'HTMLTableCellElement', 'HTMLUListElement']) {
        if (dom.window[name]) globalThis[name] = dom.window[name];
    }
    Object.defineProperty(globalThis, 'navigator', { value: dom.window.navigator, configurable: true });
    Element.prototype.scrollIntoView = () => {};
    return dom;
}
const change = element => element.dispatchEvent(new Event('change', { bubbles: true }));
const input = element => element.dispatchEvent(new Event('input', { bubbles: true }));
const tick = () => new Promise(resolve => setTimeout(resolve, 0));

test('Enter in category and tag searches does not submit or analyze the manuscript', () => {
    const dom = setup(`<form class="publishing-form"><button data-analyze type="button">Analyze</button>
        <div data-choice-picker><input data-choice-search><div data-choice-summary></div><div data-choice-list></div><div data-choice-empty></div></div>
        <div data-choice-picker><input data-choice-search><div data-choice-summary></div><div data-choice-list></div><div data-choice-empty></div><input data-new-tag><button type="button" data-add-tag>Add</button></div></form>`);
    try {
        initPublishing();
        let submissions = 0, analyses = 0;
        document.querySelector('form').addEventListener('submit', () => submissions++);
        document.querySelector('[data-analyze]').addEventListener('click', () => analyses++);
        document.querySelectorAll('[data-choice-search]').forEach(search => {
            const event = new window.KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true });
            search.dispatchEvent(event); assert.equal(event.defaultPrevented, true);
        });
        const input = document.querySelector('[data-new-tag]'); input.value = 'New topic';
        const event = new window.KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true });
        input.dispatchEvent(event);
        assert.equal(event.defaultPrevented, true);
        assert.equal(document.querySelector('[value="new:New topic"]').checked, true);
        assert.equal(submissions, 0); assert.equal(analyses, 0);
    } finally { dom.window.close(); }
});

function queuedComposer() {
    return `<form class="publishing-form" data-composer>
        <input type="radio" name="compose_mode" value="import" checked><input name="title">
        <input type="hidden" name="status" value="published">
        <section data-standard-content><textarea name="content"></textarea></section>
        <section data-import-content><textarea name="manuscript" data-upload-pending="true">CHAPTER 1 - First\nChapter body</textarea><button type="button" data-analyze>Analyze</button><div data-import-message></div></section>
        <div data-upload-pending="true" data-featured-pending><input name="image_path"></div>
        <section data-chapter-preview hidden></section><span data-save-hint></span><div data-save-message hidden></div>
        <button type="submit" data-save data-save-status="published">Save chapters</button><button type="submit" data-save-draft data-save-status="draft">Save draft</button>
    </form><form data-import-confirm></form>`;
}

test('saving chapters waits for featured and editor images then submits the chosen publish or draft action once', async () => {
    for (const status of ['published', 'draft']) {
        const dom = setup(queuedComposer());
        try {
            initPublishing();
            const form = document.querySelector('[data-composer]');
            const submitter = form.querySelector(`[data-save-status="${status}"]`);
            const confirmation = document.querySelector('[data-import-confirm]');
            let submissions = 0;
            confirmation.requestSubmit = () => { submissions++; };
            form.requestSubmit = button => form.dispatchEvent(new window.SubmitEvent('submit', { bubbles: true, cancelable: true, submitter: button }));
            form.dispatchEvent(new window.SubmitEvent('submit', { bubbles: true, cancelable: true, submitter }));
            assert.equal(form.querySelector('[data-save]').disabled, true);
            assert.equal(form.querySelector('[data-save-message]').hidden, false);
            form.querySelector('[data-featured-pending] [name="image_path"]').value = 'imports/cover.webp';
            delete form.querySelector('[data-featured-pending]').dataset.uploadPending;
            await tick(); assert.equal(submissions, 0, 'editor image is still uploading');
            const manuscript = form.querySelector('[name="manuscript"]');
            manuscript.value = 'CHAPTER 1 - First\nChapter body with an uploaded image';
            manuscript.dataset.uploadPending = 'false';
            await tick();
            assert.equal(submissions, 1);
            assert.equal(confirmation.elements.status.value, status);
            assert.equal(confirmation.elements.image_path.value, 'imports/cover.webp');
            assert.equal(confirmation.elements.content.value, manuscript.value);
        } finally { dom.window.close(); }
    }
});

test('failed image uploads cancel automatic saving and let the user retry', async () => {
    const dom = setup(queuedComposer());
    try {
        initPublishing();
        const form = document.querySelector('[data-composer]');
        const button = form.querySelector('[data-save]');
        let submissions = 0; document.querySelector('[data-import-confirm]').requestSubmit = () => submissions++;
        form.dispatchEvent(new window.SubmitEvent('submit', { bubbles: true, cancelable: true, submitter: button }));
        const image = form.querySelector('[data-featured-pending]'); image.dataset.uploadFailed = 'true'; delete image.dataset.uploadPending;
        await tick();
        assert.equal(submissions, 0); assert.equal(button.disabled, false);
        assert.match(form.querySelector('[data-save-message]').textContent, /Image upload failed/);
        assert.equal(form.hasAttribute('aria-busy'), false);
    } finally { dom.window.close(); }
});

test('featured image upload resolves the queued save with its returned media path', async () => {
    const dom = setup(`<meta name="csrf-token" content="test"><form class="publishing-form">
        <div data-image-picker data-immediate-upload><input type="file" data-image-input><input name="image_path">
        <div data-image-preview hidden><img data-image-thumbnail data-current-src=""><span data-image-caption></span><button type="button" data-image-reset hidden>Undo</button></div><p data-image-error hidden></p></div>
        <div data-save-message hidden></div><button type="submit" data-save>Save</button></form>`);
    const originalFetch = globalThis.fetch, originalCreate = URL.createObjectURL, originalRevoke = URL.revokeObjectURL;
    try {
        let finishUpload;
        globalThis.fetch = () => new Promise(resolve => { finishUpload = resolve; });
        URL.createObjectURL = () => 'blob:cover'; URL.revokeObjectURL = () => {};
        initPublishing();
        const form = document.querySelector('form'), file = form.querySelector('[data-image-input]');
        Object.defineProperty(file, 'files', { value: [new File(['image'], 'cover.png', { type: 'image/png' })], configurable: true });
        change(file);
        assert.equal(form.querySelector('[data-image-picker]').dataset.uploadPending, 'true');
        let submissions = 0;
        form.requestSubmit = () => { submissions++; assert.equal(form.elements.image_path.value, 'posts/cover.webp'); };
        form.dispatchEvent(new window.SubmitEvent('submit', { bubbles: true, cancelable: true, submitter: form.querySelector('[data-save]') }));
        assert.equal(submissions, 0);
        finishUpload({ ok: true, json: async () => ({ path: 'posts/cover.webp', url: '/storage/posts/cover.webp' }) });
        await tick();
        assert.equal(submissions, 1);
        assert.equal(form.querySelector('[data-image-thumbnail]').getAttribute('src'), '/storage/posts/cover.webp');
    } finally { globalThis.fetch = originalFetch; URL.createObjectURL = originalCreate; URL.revokeObjectURL = originalRevoke; dom.window.close(); }
});

test('new post slugs follow Vietnamese titles and respect manual overrides', () => {
    const dom = setup('<form class="publishing-form" data-auto-slug><input name="title"><details><summary>Slug</summary><input name="slug"></details></form>');
    try {
        initPublishing();
        const title = document.querySelector('[name="title"]');
        const slug = document.querySelector('[name="slug"]');
        title.value = 'Đọc truyện: Những ngày đẹp!'; input(title);
        assert.equal(slug.value, 'doc-truyen-nhung-ngay-dep');
        slug.value = 'custom-link'; input(slug);
        title.value = 'Tiêu đề mới'; input(title);
        assert.equal(slug.value, 'custom-link');
        slug.value = ''; input(slug);
        assert.equal(slug.value, 'tieu-de-moi');
        title.value = ''; input(title); assert.equal(slug.value, '');
        slug.dispatchEvent(new Event('invalid'));
        assert.equal(document.querySelector('details').open, true);
    } finally { dom.window.close(); }
});

test('existing and restored custom slugs survive initialization and title changes', () => {
    for (const attribute of ['', 'data-auto-slug']) {
        const dom = setup(`<form class="publishing-form" ${attribute}><input name="title" value="Original"><input name="slug" value="saved-link"></form>`);
        try {
            initPublishing();
            const title = document.querySelector('[name="title"]');
            title.value = 'Updated'; input(title);
            assert.equal(document.querySelector('[name="slug"]').value, 'saved-link');
        } finally { dom.window.close(); }
    }
});

test('search, image previews and chapter analysis preserve selections and invalidate stale previews', async () => {
    const dom = setup(`<form data-composer>
        <input type="radio" name="compose_mode" value="normal" checked><input type="radio" name="compose_mode" value="import">
        <section data-standard-content><input name="title"><textarea name="content"></textarea></section>
        <section data-standard-seo><input name="slug"></section><div data-field="published_at"><input name="published_at"></div>
        <section data-import-content hidden><textarea name="manuscript"></textarea><button type="button" data-analyze>Analyze chapters</button><div data-import-message></div></section>
        <select name="status"><option value="draft">Draft</option><option value="published">Published</option></select>
        <div data-choice-picker data-tag-picker><input data-choice-search><div data-choice-summary></div><div data-choice-list>
            <label class="choice-option"><input type="checkbox" name="tags[]" value="1"><span>Adventure</span></label>
            <label class="choice-option"><input type="checkbox" name="tags[]" value="2"><span>Fiction</span></label>
        </div><div data-choice-empty hidden></div><input data-new-tag><button type="button" data-add-tag>Add</button></div>
        <div data-image-picker><input type="file" data-image-input><div data-image-preview hidden><img data-image-thumbnail data-current-src="/existing.webp"><span data-image-caption></span><button type="button" data-image-reset hidden>Undo</button></div><p data-image-error hidden></p></div>
        <section data-chapter-preview hidden></section><span data-save-hint></span><button data-save>Save</button>
    </form><form data-import-confirm><input name="token"></form>`);
    const originalFetch = globalThis.fetch;
    const originalCreate = URL.createObjectURL;
    const originalRevoke = URL.revokeObjectURL;
    try {
        initPublishing();
        const tag = document.querySelector('[value="1"]'); tag.checked = true; change(tag);
        const search = document.querySelector('[data-choice-search]'); search.value = 'fiction'; input(search);
        assert.equal(tag.closest('label').hidden, true); assert.equal(tag.checked, true);
        search.value = 'missing'; input(search); assert.equal(document.querySelector('[data-choice-empty]').hidden, false);
        document.querySelector('[data-new-tag]').value = '<New tag>'; document.querySelector('[data-add-tag]').click();
        assert.equal(document.querySelector('[value="new:<New tag>"]').checked, true);
        assert.equal(document.querySelector('[data-choice-summary]').textContent, '2 selected');
        assert.equal(document.querySelector('[data-choice-list] new'), null, 'tag names must remain text');
        const file = document.querySelector('[data-image-input]');
        URL.createObjectURL = () => 'blob:preview'; URL.revokeObjectURL = () => {};
        Object.defineProperty(file, 'files', { value: [new File(['image'], 'cover.png', { type: 'image/png' })], configurable: true }); change(file);
        assert.equal(document.querySelector('[data-image-preview]').hidden, false);
        assert.equal(document.querySelector('[data-image-thumbnail]').getAttribute('src'), 'blob:preview');
        Object.defineProperty(file, 'files', { value: [new File(['bad'], 'bad.txt', { type: 'text/plain' })], configurable: true }); change(file);
        assert.equal(document.querySelector('[data-image-error]').hidden, false);
        assert.equal(document.querySelector('[data-image-thumbnail]').getAttribute('src'), '/existing.webp');
        const mode = document.querySelector('[value="import"]'); mode.checked = true; change(mode);
        const save = document.querySelector('[data-save]');
        assert.equal(document.querySelector('[data-standard-content]').hidden, true); assert.equal(save.disabled, false);
        let requests = 0;
        globalThis.fetch = () => { requests++; throw new Error('Analyze must not call the server'); };
        const source = document.querySelector('[name="manuscript"]');
        source.value = '<h2>CHAPTER 1 - First</h2><p>Body</p><h2>CHAPTER 3 - Next</h2><p>Next body</p>';
        document.querySelector('[data-analyze]').click(); await tick();
        assert.equal(requests, 0);
        assert.equal(document.querySelector('[data-chapter-preview]').hidden, false);
        assert.equal(document.querySelectorAll('[data-chapter-preview] details').length, 2);
        const chapters = document.querySelectorAll('[data-chapter-preview] .chapter-preview');
        assert.equal(chapters.length, 2);
        assert.equal(chapters[0].open, true);
        assert.equal(chapters[1].open, false);
        assert.equal(chapters[0].querySelector('.chapter-preview-number').textContent, '1');
        assert.equal(chapters[0].querySelector('.flex-grow-1').firstChild.textContent, 'First');
        assert.equal(chapters[0].querySelector('.chapter-preview-content.ck-content p').textContent, 'Body');
        assert.match(document.querySelector('[data-chapter-preview]').textContent, /Non-consecutive/);
        const originalSource = source.value;
        source.value = 'No headings'; input(source);
        document.querySelector('[data-analyze]').click(); await tick();
        assert.match(document.querySelector('[data-import-message]').textContent, /No CHAPTER/);
        assert.equal(save.disabled, false);
        source.value = originalSource; input(source);
        document.querySelector('[data-analyze]').click(); await tick();
        let submitted = false;
        const confirm = document.querySelector('[data-import-confirm]'); confirm.requestSubmit = () => { submitted = true; };
        document.querySelector('[data-composer]').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        assert.equal(submitted, true);
        assert.equal(confirm.getAttribute('action'), '/admin/import/save');
        assert.equal(confirm.elements.content.value, source.value);
        assert.equal(requests, 0);

    } finally { globalThis.fetch = originalFetch; URL.createObjectURL = originalCreate; URL.revokeObjectURL = originalRevoke; dom.window.close(); }
});

test('copy link writes the public URL and confirms success', async () => {
    const dom = setup('<button data-copy-link="https://example.org/articles/story">Copy link</button>');
    let copied;
    Object.defineProperty(window, 'isSecureContext', { value: true });
    Object.defineProperty(navigator, 'clipboard', { value: { writeText: async value => { copied = value; } } });
    initCopyLinks(); document.querySelector('button').click(); await tick();
    assert.equal(copied, 'https://example.org/articles/story'); assert.equal(document.querySelector('button').title, 'Link copied!');
    dom.window.close();
});

test('CKEditor initializes its upload plugin and exposes rich text commands', async () => {
    const dom = setup('<form><textarea name="content" data-editor></textarea></form>');
    globalThis.getComputedStyle = dom.window.getComputedStyle;
    globalThis.requestAnimationFrame = dom.window.requestAnimationFrame;
    globalThis.cancelAnimationFrame = dom.window.cancelAnimationFrame;
    globalThis.ResizeObserver = class { observe() {} unobserve() {} disconnect() {} };
    window.ResizeObserver = globalThis.ResizeObserver;
    window.matchMedia = () => ({ matches: false, addEventListener() {}, removeEventListener() {} });
    window.scrollTo = () => {};
    Range.prototype.getClientRects = () => [];
    Range.prototype.getBoundingClientRect = () => ({ top: 0, left: 0, right: 0, bottom: 0, width: 0, height: 0 });
    const { initEditors } = await import('../../resources/js/editor.js');
    initEditors();
    const textarea = document.querySelector('textarea');
    try {
        for (let i = 0; i < 50 && !textarea.editor; i++) await new Promise(resolve => setTimeout(resolve, 100));
        assert.ok(textarea.editor, 'The editor must initialize, including the custom upload plugin.');
        const editor = textarea.editor;
        for (const command of ['bold', 'italic', 'underline', 'strikethrough', 'uploadImage', 'insertTable']) assert.ok(editor.commands.get(command), command);
        editor.setData('<p><strong>Bold</strong> <em>Italic</em> <u>Underline</u></p>');
        assert.match(textarea.value, /<strong>Bold<\/strong>/);
        assert.ok(editor.plugins.get('FileRepository').createUploadAdapter);
        const pending = editor.plugins.get('PendingActions'); const action = pending.add('Uploading image');
        assert.equal(textarea.dataset.uploadPending, 'true'); pending.remove(action); assert.equal(textarea.dataset.uploadPending, 'false');
    } finally { if (textarea.editor) await textarea.editor.destroy(); dom.window.close(); }
});
