import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { initSearchableSelects } from '../../resources/js/searchable-select.js';

test('search selection hides search until opened, matches Vietnamese and submits the selected value', () => {
    const dom = new JSDOM('<form><select name="category_id" data-searchable-select data-search-label="Search categories..."><option value="">All categories</option><option value="1" selected>Fantasy</option><option value="2">Đọc truyện</option><option value="3">Drama</option></select></form>');
    globalThis.document = dom.window.document;
    globalThis.Event = dom.window.Event;
    globalThis.window = dom.window;
    try {
        initSearchableSelects(); initSearchableSelects();
        const search = document.querySelector('input');
        const select = document.querySelector('select');
        const trigger = document.querySelector('button');
        const panel = document.querySelector('.searchable-select-panel');
        const rows = () => [...document.querySelectorAll('[role="option"]')].map(row => row.textContent);
        const input = value => { search.value = value; search.dispatchEvent(new Event('input')); };
        assert.equal(document.querySelectorAll('input').length, 1);
        assert.equal(panel.hidden, true);
        assert.equal(select.hidden, true);
        assert.equal(trigger.textContent, 'Fantasy');
        trigger.click();
        assert.equal(panel.hidden, false);
        assert.equal(document.activeElement, search);
        input('doc');
        assert.deepEqual(rows(), ['Đọc truyện']);
        assert.equal(select.value, '1');
        assert.equal(new dom.window.FormData(document.querySelector('form')).get('category_id'), '1');
        input('missing');
        assert.deepEqual(rows(), []);
        assert.equal(document.querySelector('.searchable-select-empty').hidden, false);
        input('DOC');
        document.querySelector('[role="option"]').click();
        assert.equal(select.value, '2');
        assert.equal(trigger.textContent, 'Đọc truyện');
        assert.equal(panel.hidden, true);
        assert.equal(new dom.window.FormData(document.querySelector('form')).get('category_id'), '2');
        trigger.click();
        assert.equal(rows().length, 4);
        input('');
        search.dispatchEvent(new dom.window.KeyboardEvent('keydown', { key: 'ArrowDown', cancelable: true }));
        const enter = new dom.window.KeyboardEvent('keydown', { key: 'Enter', cancelable: true });
        search.dispatchEvent(enter);
        assert.equal(enter.defaultPrevented, true);
        assert.equal(select.value, '3');
        assert.equal(panel.hidden, true);
        trigger.click();
        search.dispatchEvent(new dom.window.KeyboardEvent('keydown', { key: 'Escape', cancelable: true }));
        assert.equal(panel.hidden, true);
        assert.equal(document.activeElement, trigger);
        trigger.click();
        document.body.dispatchEvent(new dom.window.Event('pointerdown', { bubbles: true }));
        assert.equal(panel.hidden, true);
        assert.equal(select.options.length, 4);
        assert.equal(select.value, '3');
    } finally { dom.window.close(); }
});

test('opening another filter closes the previous one and reset restores the default choice', async () => {
    const dom = new JSDOM('<form><select name="creator" data-searchable-select data-search-label="Search creators..."><option value="">All</option><option value="1">Writer</option></select><select name="series" data-searchable-select data-search-label="Search stories..."><option value="">All stories</option><option value="2">Story</option></select></form>');
    globalThis.document = dom.window.document;
    globalThis.Event = dom.window.Event;
    globalThis.window = dom.window;
    try {
        initSearchableSelects();
        const buttons = [...document.querySelectorAll('button')];
        const panels = [...document.querySelectorAll('.searchable-select-panel')];
        buttons[0].click(); buttons[1].click();
        assert.equal(panels[0].hidden, true);
        assert.equal(panels[1].hidden, false);
        panels[1].querySelectorAll('[role="option"]')[1].click();
        assert.equal(buttons[1].textContent, 'Story');
        document.querySelector('form').reset();
        await new Promise(resolve => setTimeout(resolve, 10));
        assert.equal(buttons[1].textContent, 'All stories');
        assert.equal(panels[1].hidden, true);
    } finally { dom.window.close(); }
});
