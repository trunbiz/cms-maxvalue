import { ClassicEditor, Essentials, Paragraph, Bold, Italic, Underline, Strikethrough, RemoveFormat, Link, List, Heading, BlockQuote, Image, ImageUpload, ImageToolbar, ImageCaption, ImageStyle, ImageTextAlternative, Table, TableToolbar, PendingActions } from 'ckeditor5';
class UploadAdapter {
    constructor(loader) { this.loader = loader; this.controller = new AbortController(); }
    async upload() {
        const data = new FormData(); data.append('upload', await this.loader.file);
        const response = await fetch('/admin/upload/editor', { method: 'POST', body: data, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' }, signal: this.controller.signal });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.url) throw new Error(result.errors?.upload?.[0] || result.message || 'Unable to upload the image.');
        return { default: result.url };
    }
    abort() { this.controller.abort(); }
}
function ImageUploadAdapter(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = loader => new UploadAdapter(loader);
}
export function initEditors() {
    document.querySelectorAll('[data-editor]').forEach(element => {
        ClassicEditor.create(element, {
            licenseKey: 'GPL', language: 'en',
            plugins: [Essentials, Paragraph, Bold, Italic, Underline, Strikethrough, RemoveFormat, Link, List, Heading, BlockQuote, Image, ImageUpload, ImageToolbar, ImageCaption, ImageStyle, ImageTextAlternative, Table, TableToolbar, PendingActions],
            toolbar: { items: ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'underline', 'strikethrough', 'removeFormat', '|', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'uploadImage', 'insertTable'], shouldNotGroupWhenFull: true },
            heading: { options: [{ model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' }, { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' }, { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }, { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }] },
            image: { toolbar: ['imageTextAlternative', 'toggleImageCaption'] }, table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
            extraPlugins: [ImageUploadAdapter],
        }).then(editor => {
            element.editor = editor;
            editor.model.document.on('change:data', () => { element.value = editor.getData(); element.dispatchEvent(new Event('input', { bubbles: true })); });
            editor.plugins.get('PendingActions').on('change:hasAny', () => {
                element.dataset.uploadPending = editor.plugins.get('PendingActions').hasAny ? 'true' : 'false';
            });
            element.form.addEventListener('submit', event => {
                element.value = editor.getData();
                if (editor.plugins.get('PendingActions').hasAny) {
                    event.preventDefault();
                    alert('Please wait for the image upload to finish before saving.');
                }
            });
        }).catch(() => { const note = document.createElement('p'); note.className = 'text-danger'; note.textContent = 'The editor could not load. You can still enter content in the text area.'; element.after(note); });
    });
}
