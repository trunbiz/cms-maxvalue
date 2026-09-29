# English publishing update

The reader interface, administration screens, editor, notifications, and canonical route prefixes now use English.

Added six editable publication-policy drafts, publisher identity settings, private previews, article bylines and structured metadata, AdSense verification metadata and ads.txt, and a publication checklist. Existing authored content is preserved; untouched demo content is kept as drafts.

## Verification

- Feature suite: 29 tests passed, 242 assertions.
- Production Vite build passed.
- Fresh migrations and seeding passed on a disposable SQLite database.
- Route cache and Blade compilation passed.
- Local HTTP checks passed for public pages, article creation, settings, and private page previews.
- Browser visual verification was unavailable.

## Remaining publisher tasks

Enter the real publication identity, contact email, public HTTPS domain, and your own AdSense Publisher ID. Review and publish the six policy drafts, write original articles, deploy publicly, and request review through your AdSense account. Configure applicable consent management before serving ads. No application has been submitted and approval is not guaranteed.

See [ADSENSE_SETUP.md](ADSENSE_SETUP.md) for detailed instructions.

## Modified or deleted files

- `AGENTS.md`
- `README.md`
- `app/Console/Commands/FlushViews.php`
- `app/Console/Commands/MigrateMediaToR2.php`
- `app/Console/Commands/TestR2.php`
- `app/Http/Controllers/Admin/AuthController.php`
- `app/Http/Controllers/Admin/ChapterImportController.php`
- `app/Http/Controllers/Admin/MenuItemsController.php`
- `app/Http/Controllers/Admin/ResourceController.php`
- `app/Http/Controllers/Admin/SettingsController.php`
- `app/Http/Controllers/Frontend/ReadingController.php`
- `app/Http/Middleware/CheckModule.php`
- `app/Http/Requests/BrowseRequest.php`
- `app/Http/Requests/MenuItemsRequest.php`
- `app/Http/Requests/ResourceRequest.php`
- `app/Http/Requests/SettingsRequest.php`
- `app/Models/Page.php`
- `app/Models/Post.php`
- `app/Models/Role.php`
- `app/Models/Series.php`
- `app/Services/ChapterImportService.php`
- `app/Services/CloudflareService.php`
- `app/Services/MediaService.php`
- `app/Services/MenuService.php`
- `app/Services/ResourceService.php`
- `app/Services/SiteService.php`
- `app/helpers.php`
- `config/app.php`
- `config/cms.php`
- `config/modules.php`
- `database/factories/PageFactory.php`
- `database/seeders/DatabaseSeeder.php`
- `public/images/book-placeholder.svg`
- `public/robots.txt`
- `resources/css/app.css`
- `resources/js/editor.js`
- `resources/js/menu.js`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/form.blade.php`
- `resources/views/admin/import-preview.blade.php`
- `resources/views/admin/import.blade.php`
- `resources/views/admin/index.blade.php`
- `resources/views/admin/layout.blade.php`
- `resources/views/admin/login.blade.php`
- `resources/views/admin/menu-editor.blade.php`
- `resources/views/admin/settings.blade.php`
- `resources/views/frontend/article.blade.php`
- `resources/views/frontend/chapter-nav.blade.php`
- `resources/views/frontend/chapter.blade.php`
- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/layout.blade.php`
- `resources/views/frontend/listing.blade.php`
- `resources/views/frontend/menu.blade.php`
- `resources/views/frontend/post-card.blade.php`
- `resources/views/frontend/series-card.blade.php`
- `resources/views/frontend/series.blade.php`
- `routes/frontend.php`
- `routes/web.php`
- `tests/Feature/CmsTest.php`

## Added files

- `app/Console/Commands/PrepareEnglishPublication.php`
- `app/Http/Controllers/Frontend/LegacyRedirectController.php`
- `app/Http/Requests/PublishPagesRequest.php`
- `app/Services/EnglishPublicationService.php`
- `app/Services/PublisherService.php`
- `database/migrations/2026_09_29_000002_add_publishing_metadata.php`
- `database/seeders/PublicationPagesSeeder.php`
- `docs/ADSENSE_SETUP.md`
- `lang/en/auth.php`
- `lang/en/pagination.php`
- `lang/en/passwords.php`
- `lang/en/validation.php`
- `tests/Feature/PublisherTest.php`
- `docs/ENGLISH_CHANGES.md`
