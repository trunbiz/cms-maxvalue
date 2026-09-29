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
        assert.equal(document.querySelector('[data-standard-content]').hidden, true); assert.equal(save.disabled, true);
        let resolve;
        globalThis.fetch = () => new Promise(callback => { resolve = callback; });
        document.querySelector('[data-analyze]').click();
        resolve({ ok: true, json: async () => ({ token: 'reviewed-token', html: '<p>Full chapter preview</p>' }) }); await tick();
        assert.equal(save.disabled, false); assert.equal(document.querySelector('[data-chapter-preview]').hidden, false);
        let submitted = false;
        const confirm = document.querySelector('[data-import-confirm]'); confirm.requestSubmit = () => { submitted = true; };
        document.querySelector('[data-composer]').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        assert.equal(submitted, true); assert.equal(confirm.elements.token.value, 'reviewed-token');
        change(document.querySelector('[name="status"]')); assert.equal(save.disabled, true);
        document.querySelector('[data-analyze]').click(); input(document.querySelector('[name="manuscript"]'));
        resolve({ ok: true, json: async () => ({ token: 'stale', html: '<p>Old preview</p>' }) }); await tick();
        assert.equal(save.disabled, true); assert.equal(document.querySelector('[data-chapter-preview]').hidden, true);
        document.querySelector('[data-analyze]').click(); resolve({ ok: false, json: async () => ({ errors: { content: ['No chapter headings found.'] } }) }); await tick();
        assert.match(document.querySelector('[data-import-message]').textContent, /No chapter headings/);
        assert.equal(save.disabled, true); assert.equal(document.querySelector('[data-analyze]').disabled, false);
    } finally { globalThis.fetch = originalFetch; URL.createObjectURL = originalCreate; URL.revokeObjectURL = originalRevoke; dom.window.close(); }
});

test('copy link writes the public URL and confirms success', async () => {
    const dom = setup('<button data-copy-link="https://example.org/articles/story">Copy link</button>');
    let copied;
    Object.defineProperty(window, 'isSecureContext', { value: true });
    Object.defineProperty(navigator, 'clipboard', { value: { writeText: async value => { copied = value; } } });
    initCopyLinks(); document.querySelector('button').click(); await tick();
    assert.equal(copied, 'https://example.org/articles/story'); assert.equal(document.querySelector('button').textContent, 'Link copied!');
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
