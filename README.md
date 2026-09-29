# Reading Corner — Laravel 10 publishing CMS

An English-language reading website and CMS built with Laravel 10, PHP 8.1, MySQL 8, Redis, Blade, Bootstrap 5.3, Vite, vanilla JavaScript, SortableJS, and CKEditor 5. Fonts are self-hosted through npm.

## Open the local application

- Website: http://127.0.0.1:8010
- Administration: http://127.0.0.1:8010/admin
- Write an article: http://127.0.0.1:8010/admin/posts/create
- Publisher profile and AdSense preparation: http://127.0.0.1:8010/admin/settings
- Seed login: **admin / admin123**. Change the password before production.

The local PHP executable is at C:\OpenServer\modules\php\PHP_8.1\php.exe. Composer is at C:\OpenServer\userdata\composer\composer.phar. MySQL and Redis must be running.

## Install and run

~~~powershell
$env:Path = 'C:\OpenServer\modules\php\PHP_8.1;' + $env:Path
php C:\OpenServer\userdata\composer\composer.phar install
npm ci
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8010
~~~

For an existing installation, run migrations without reseeding, then run the idempotent English preparation command:

~~~bash
php artisan migrate
php artisan site:prepare-english
~~~

This preserves authored content, converts unchanged legacy examples into English drafts, and creates missing publication pages. Policy pages remain drafts until the publisher profile and a content review are complete. Use the CMS to publish your own original work.

If you use an OpenServer domain, set its document root to public/ and update APP_URL in .env. Keep .env out of Git.

## Publishing and AdSense

Read [the publication and AdSense setup guide](docs/ADSENSE_SETUP.md) before launch. Six editable policy pages are prepared: About, Contact, Privacy Policy, Terms of Use, Editorial Policy, and Copyright & Corrections.

Articles support drafts, authenticated previews, publication dates, featured images, SEO metadata, Article JSON-LD, tags, and related reading. AdSense settings generate ownership verification and ads.txt from your own Publisher ID. No advertisements or tracking scripts are enabled automatically, and no application is submitted automatically.

The application is prepared for publishing; Google approval requires a real public site with appropriate original content and accurate policies. A localhost URL and demo drafts are not ready for submission.

## Media and Cloudflare R2

All uploaded images pass through MediaService, are resized to a maximum width of 1200px, and converted to WebP. The database stores relative paths. Inline images use /media/ path markers, resolved by media_url() at render time. External pasted images are removed on save; upload permitted images through the editor instead.

Local storage uses MEDIA_DISK=public. For R2, fill in the R2 variables in .env.example, configure the bucket public domain, then run:

~~~bash
php artisan config:clear
php artisan r2:test
php artisan media:migrate-to-r2
~~~

The migration command skips existing R2 objects and keeps local files. Switch MEDIA_DISK to r2 after checking the result. Live R2 connectivity has not been verified because credentials are not configured.

## Cache, queue, and view counts

Public HTML is shared for all readers and does not require a session cookie. Settings, menus, and categories are cached. Admin changes invalidate response caches and queue Cloudflare URL purges when enabled. Use distinct Redis/cache prefixes for each project.

~~~bash
php artisan queue:work redis --tries=3 --timeout=300
php artisan schedule:work
~~~

In production, invoke schedule:run every minute with cron or Task Scheduler. View counts accumulate in Redis and flush every ten minutes. A database batch identifier prevents double-counting when a flush is retried after a crash.

Debugbar can be enabled in local administration with DEBUGBAR_ENABLED=true. It is disabled on public pages to avoid putting debug data into shared HTML.

## Chapter import

The importer accepts plain text and editor HTML, with headings such as CHAPTER 1 - Title, Chapter 2 – Title, or Chapter 3: Title. For a new story, put its title on the first line and its description before the first chapter. Existing stories can receive new chapters with skip/overwrite duplicate handling.

A user-scoped preview must be confirmed before insertion. Each import is transactional and uses batch inserts. Preview lifetime: one hour. Limits: 2,000 chapters, five million characters, image size 5MB and 40 million pixels. Featured images shared by multiple records are retained until no record uses them.

## Verification

~~~bash
php artisan test
npm run test:ui
php vendor/bin/pint --test
npm run build
php artisan route:cache
php artisan route:clear
~~~

Tests use SQLite in memory. Redis integration tests use isolated UUID prefixes and report skipped if Redis is unavailable. Coverage includes permissions, chapter parsing and rollback, media, menu nesting, shared caching, public metadata, policy publication, AdSense verification, old URL redirects, and view-count recovery.

Use migrate:fresh --seed only on a disposable development database: it deletes existing tables. Fresh seeds contain 155 demo posts/chapters as drafts, five draft stories, three categories, ten tags, and six policy drafts.

PHP dependencies are resolved for PHP 8.1.1. Laravel 10 is retained as required; Composer audit reports framework advisories for this version. Browser visual verification was unavailable in the tool environment; HTTP and Feature tests were used.

Changes for the English publishing update are listed in [docs/ENGLISH_CHANGES.md](docs/ENGLISH_CHANGES.md).

See [docs/PUBLISHING_EDITOR.md](docs/PUBLISHING_EDITOR.md) for the grouped editor, searchable category/tag checkboxes, image previews, copy links, and inline chapter analysis workflow.

Files changed in the editor update are listed in [docs/EDITOR_CHANGES.md](docs/EDITOR_CHANGES.md).
