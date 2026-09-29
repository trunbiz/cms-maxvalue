import { ClassicEditor, Essentials, Paragraph, Bold, Italic, Link, List, Heading, BlockQuote, Image, ImageUpload, ImageToolbar, ImageCaption, ImageStyle, Table, TableToolbar } from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';
import vi from 'ckeditor5/translations/vi.js';

class UploadAdapter {
    constructor(loader) { this.loader = loader; this.controller = new AbortController(); }
    async upload() {
        const data = new FormData(); data.append('upload', await this.loader.file);
        const response = await fetch('/admin/upload/editor', { method: 'POST', body: data, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' }, signal: this.controller.signal });
        const result = await response.json();
        if (!response.ok || !result.url) throw new Error(result.errors?.upload?.[0] || result.message || 'Không thể tải ảnh lên.');
        return { default: result.url };
    }
    abort() { this.controller.abort(); }
}
export function initEditors() {
    document.querySelectorAll('[data-editor]').forEach(element => {
        ClassicEditor.create(element, {
            licenseKey: 'GPL', language: 'vi', translations: [vi],
            plugins: [Essentials, Paragraph, Bold, Italic, Link, List, Heading, BlockQuote, Image, ImageUpload, ImageToolbar, ImageCaption, ImageStyle, Table, TableToolbar],
            toolbar: ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'uploadImage', 'insertTable'],
            image: { toolbar: ['imageTextAlternative', 'toggleImageCaption'] }, table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
            extraPlugins: [editor => { editor.plugins.get('FileRepository').createUploadAdapter = loader => new UploadAdapter(loader); }],
        }).then(editor => {
            editor.model.document.on('change:data', () => { element.value = editor.getData(); });
            element.form.addEventListener('submit', () => { element.value = editor.getData(); });
        }).catch(() => { const note = document.createElement('p'); note.className = 'text-danger'; note.textContent = 'Không tải được trình soạn thảo. Bạn vẫn có thể nhập nội dung vào ô văn bản.'; element.after(note); });
    });
}
