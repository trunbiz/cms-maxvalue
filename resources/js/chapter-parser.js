const t = (text, values = {}) => Object.entries(values).reduce((result, [key, value]) => result.replaceAll(`:${key}`, String(value)), window.adminTranslations?.[text] || text);

export function chapterPreview(html) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const fragment = document.createDocumentFragment();
    const allowed = ['P','H1','H2','H3','H4','H5','H6','STRONG','B','EM','I','U','S','BR','HR','UL','OL','LI','BLOCKQUOTE','FIGURE','FIGCAPTION','IMG','TABLE','THEAD','TBODY','TR','TH','TD','A'];
    const copy = (node, parent) => {
        if (node.nodeType === 3) { parent.append(document.createTextNode(node.textContent)); return; }
        if (node.nodeType !== 1 || ['SCRIPT','STYLE','IFRAME','OBJECT','SVG','MATH'].includes(node.tagName)) return;
        let target = parent;
        if (allowed.includes(node.tagName)) {
            target = document.createElement(node.tagName.toLowerCase());
            for (const attr of ['src','href']) {
                const value = node.getAttribute(attr);
                if (value && /^(?:https?:\/\/|\/(?!\/))/i.test(value.trim()) && !value.includes('\\')) target.setAttribute(attr, value);
            }
            if (node.tagName === 'IMG') { target.alt = node.getAttribute('alt') || ''; target.loading = 'lazy'; target.className = 'img-fluid'; }
            parent.append(target);
        }
        [...node.childNodes].forEach(child => copy(child, target));
    };
    [...doc.body.childNodes].forEach(node => copy(node, fragment));
    return fragment;
}
export function parseChapters(source) {
    const doc = new DOMParser().parseFromString(source.includes('<') ? source : source.split(/\r?\n/).map(line => {
        const p = document.createElement('p'); p.textContent = line; return p.outerHTML;
    }).join(''), 'text/html');
    const blocks = [];
    const heading = /^CHAPTER\s+(\d+)\s*[-–:]\s*(.+)$/iu;
    const walk = node => {
        if (node.nodeType === 3) {
            node.textContent.split(/\r?\n/).filter(line => line.trim()).forEach(line => {
                const p = document.createElement('p'); p.textContent = line; blocks.push(p);
            });
        } else if (['BODY','DIV','SECTION','ARTICLE'].includes(node.tagName)) [...node.childNodes].forEach(walk);
        else if (node.nodeType === 1) {
            const parts = node.innerHTML.split(/<br\s*\/?\s*>/i);
            if (parts.length > 1 && parts.some(part => {
                const p = document.createElement('p'); p.innerHTML = part; return heading.test(p.textContent.trim());
            })) parts.forEach(part => { const p = document.createElement('p'); p.innerHTML = part; blocks.push(p); });
            else blocks.push(node);
        }
    };
    walk(doc.body);
    const chapters = [], warnings = [], seen = new Set(); let current;
    for (const block of blocks) {
        const match = block.textContent.trim().match(heading);
        if (match) {
            const number = Number(match[1]);
            if (number < 1 || number > 1000000 || match[2].length > 255) throw new Error(t('Invalid chapter number or title.'));
            if (seen.has(number)) warnings.push(t('Chapter :number appears more than once.', { number }));
            if (current && number !== current.number + 1) warnings.push(t('Non-consecutive chapter numbers: :previous → :number.', { previous: current.number, number }));
            current = { number, title: match[2], text: '', html: '' }; chapters.push(current); seen.add(number);
        } else if (current) { current.text += block.textContent + '\n'; current.html += block.outerHTML; }
    }
    if (!chapters.length) throw new Error(t('No CHAPTER X - Title heading was found.'));
    if (chapters.length > 2000) throw new Error(t('You can import up to 2,000 chapters at a time.'));
    for (const chapter of chapters) {
        chapter.words = (chapter.text.match(/[\p{L}\p{N}]+/gu) || []).length;
        if (!chapter.words) warnings.push(t('Chapter :number has no content.', { number: chapter.number }));
    }
    return { chapters, warnings };
}
