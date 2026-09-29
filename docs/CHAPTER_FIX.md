# Chapter visibility and shared-cover fix

The reported chapter was Published while its parent story was Draft. The public route correctly required both to be published. The reported story is now Published; the two other chapters remain Draft. Its existing cover was applied to all three chapters without replacing any chapter-specific image. The exact reported chapter URL and its image both returned HTTP 200 after the repair.

The shared-cover checkbox is now visible below the image picker and enabled by default in manuscript mode. Imports can use an existing story cover without another upload. Overwriting a chapter without sharing an image preserves its existing image. Shared covers appear in analysis previews and on public chapter pages.

The chapter editor now requires explicit parent-story publication when a chapter is published under a draft story. This prevents saving a Published chapter whose public link remains blocked by its parent status.

Local APP_URL was changed to http://cms.local. No credentials were changed.

Validation: 37 PHP tests (341 assertions), 3 JavaScript tests, production build, Blade compilation, and actual local HTTP checks.

## Changed files

- `.env` (local only, ignored by Git)
- `app/Http/Controllers/Admin/ChapterImportController.php`
- `app/Http/Requests/ResourceRequest.php`
- `app/Services/ChapterImportService.php`
- `app/Services/ResourceService.php`
- `resources/js/publishing.js`
- `resources/views/admin/form.blade.php`
- `resources/views/admin/chapter-preview.blade.php`
- `resources/views/frontend/chapter.blade.php`
- `tests/Feature/PublishingEditorTest.php`
- `docs/PUBLISHING_EDITOR.md`
- `docs/CHAPTER_FIX.md`
