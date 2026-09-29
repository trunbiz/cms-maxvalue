# Publishing editor changes

Implemented the grouped English editor, real CKEditor formatting and image uploads, searchable checkbox selections for multiple tags and categories, instant image previews, removal of author bylines, public-link copying, and manuscript analysis with full chapter previews in the same form.

The CKEditor startup failure was fixed by replacing the arrow-function extra plugin with a constructible plugin function. Image storage continues through MediaService with relative database paths.

## Verification

- 35 PHP tests passed (315 assertions).
- 3 JavaScript DOM tests passed, including real CKEditor initialization.
- npm run build passed.
- Pint, Blade compilation and git diff whitespace checks passed.
- Fresh migrations and seeding passed on a disposable SQLite database.
- The additive category migration was applied to the local MySQL database.
- Browser visual verification was unavailable; DOM and HTTP checks were used.

See [PUBLISHING_EDITOR.md](PUBLISHING_EDITOR.md) for the writing and import workflow.

## Modified or deleted files

- `README.md`
- `app/Http/Controllers/Admin/ChapterImportController.php`
- `app/Http/Controllers/Admin/ResourceController.php`
- `app/Http/Controllers/Frontend/ReadingController.php`
- `app/Http/Requests/ChapterImportRequest.php`
- `app/Http/Requests/ResourceRequest.php`
- `app/Models/Post.php`
- `app/Models/Series.php`
- `app/Services/ChapterImportService.php`
- `app/Services/ResourceService.php`
- `config/cms.php`
- `docs/ADSENSE_SETUP.md`
- `package-lock.json`
- `package.json`
- `resources/css/app.css`
- `resources/js/app.js`
- `resources/js/editor.js`
- `resources/views/admin/form.blade.php`
- `resources/views/admin/import-preview.blade.php`
- `resources/views/admin/import.blade.php`
- `resources/views/admin/index.blade.php`
- `resources/views/admin/layout.blade.php`
- `resources/views/admin/settings.blade.php`
- `resources/views/frontend/article.blade.php`
- `resources/views/frontend/chapter.blade.php`
- `tests/Feature/CmsTest.php`
- `tests/Feature/PublisherTest.php`

## Added files

- `database/migrations/2026_09_30_000003_add_multiple_categories.php`
- `docs/PUBLISHING_EDITOR.md`
- `resources/js/copy-link.js`
- `resources/js/editor-loader.js`
- `resources/js/publishing.js`
- `resources/views/admin/chapter-preview.blade.php`
- `resources/views/admin/fields/choices.blade.php`
- `resources/views/admin/fields/image.blade.php`
- `resources/views/admin/fields/input.blade.php`
- `tests/Feature/PublishingEditorTest.php`
- `tests/Js/publishing.test.mjs`
- `docs/EDITOR_CHANGES.md`
