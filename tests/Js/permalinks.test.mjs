import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { initPermalinks } from '../../resources/js/permalinks.js';

function setup() {
    const dom = new JSDOM(`<form><section data-permalink-settings>
        ${['plain','day','month','numeric','name','custom'].map(value => `<input type="radio" name="permalink_structure" value="${value}" ${value === 'name' ? 'checked' : ''}>`).join('')}
        <input name="permalink_custom" data-permalink-input value="/%postname%/" disabled>
        <code data-permalink-preview></code><button type="button" data-permalink-token="%year%">Year</button><button type="button" data-permalink-token="%postname%">Name</button>
        </section></form>`);
    globalThis.document = dom.window.document; globalThis.Event = dom.window.Event;
    const section = document.querySelector('section');
    section.dataset.permalinkPresets = JSON.stringify({plain:'/?p=%post_id%',day:'/%year%/%monthnum%/%day%/%postname%/',month:'/%year%/%monthnum%/%postname%/',numeric:'/archives/%post_id%/',name:'/%postname%/'});
    section.dataset.permalinkTokens = JSON.stringify({'%year%':'2026','%monthnum%':'10','%day%':'05','%post_id%':'123','%postname%':'sample-post'});
    section.dataset.baseUrl = 'https://example.org'; section.dataset.customStructure = '/read/%postname%/';
    initPermalinks();
    return dom;
}

test('permalink selection updates the displayed pattern and example URL for every preset', () => {
    const dom = setup();
    try {
        const input = document.querySelector('[data-permalink-input]'), preview = document.querySelector('[data-permalink-preview]');
        for (const [mode,path] of Object.entries({plain:'/?p=123',day:'/2026/10/05/sample-post/',month:'/2026/10/sample-post/',numeric:'/archives/123/',name:'/sample-post/',custom:'/read/sample-post/'})) {
            const choice = document.querySelector(`[value="${mode}"]`); choice.checked = true; choice.dispatchEvent(new Event('change'));
            assert.equal(preview.textContent, 'https://example.org' + path);
            assert.equal(input.disabled, mode !== 'custom');
            assert.equal(new dom.window.FormData(document.querySelector('form')).has('permalink_custom'), mode === 'custom');
        }
        input.value = '/blog/%post_id%/'; input.dispatchEvent(new Event('input'));
        assert.equal(preview.textContent, 'https://example.org/blog/123/');
        const name = document.querySelector('[value="name"]'); name.checked = true; name.dispatchEvent(new Event('change'));
        const custom = document.querySelector('[value="custom"]'); custom.checked = true; custom.dispatchEvent(new Event('change'));
        assert.equal(input.value, '/blog/%post_id%/');
    } finally { dom.window.close(); }
});

test('token controls enable custom editing, insert a path segment, and prevent duplicate tokens', () => {
    const dom = setup();
    try {
        document.querySelector('[data-permalink-token="%year%"]') .click();
        const input = document.querySelector('[data-permalink-input]');
        assert.equal(document.querySelector('[value="custom"]').checked, true);
        assert.equal(input.disabled, false);
        assert.equal(input.value, '/read/%postname%/%year%/');
        assert.equal(document.querySelector('[data-permalink-preview]').textContent, 'https://example.org/read/sample-post/2026/');
        assert.equal(document.querySelector('[data-permalink-token="%year%"]') .disabled, true);
        assert.equal(document.querySelector('[data-permalink-token="%postname%"]') .disabled, true);
    } finally { dom.window.close(); }
});
