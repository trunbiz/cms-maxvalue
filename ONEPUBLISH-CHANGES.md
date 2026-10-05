# OnePublish changes

Branding and site metadata are fixed in config/publication.php. Settings only accepts custom head HTML and post permalink configuration. Admin page and menu endpoints are disabled.

Static page templates: resources/views/frontend/pages/{about,contact,privacy-policy,terms-of-use,editorial-policy,copyright}.blade.php. Add your own content inside the content section.

Post checkboxes select items on the current list page. Bulk actions publish, unpublish, or move selected posts to the bin and check ownership atomically. Chapter permalinks remain unchanged. Existing /articles/{slug} URLs continue to resolve.

The requested menu spelling Liferature is retained; that listing includes categories with slug liferature or literature.

Validation: 59 tests passed (579 assertions); npm run build passed; migrate:fresh --seed passed using an isolated SQLite database. The existing MySQL database was not reset.

## Created

- app/Http/Requests/BulkPostsRequest.php
- app/Services/PermalinkService.php
- config/publication.php
- tests/Feature/OnePublishTest.php
- resources/views/frontend/pages/about.blade.php
- resources/views/frontend/pages/contact.blade.php
- resources/views/frontend/pages/copyright.blade.php
- resources/views/frontend/pages/editorial-policy.blade.php
- resources/views/frontend/pages/privacy-policy.blade.php
- resources/views/frontend/pages/terms-of-use.blade.php

## Modified

- app/Http/Controllers/Admin/ResourceController.php
- app/Http/Controllers/Admin/SettingsController.php
- app/Http/Controllers/Frontend/ReadingController.php
- app/Http/Middleware/ResourceModule.php
- app/Http/Requests/SettingsRequest.php
- app/Services/MediaService.php
- app/Services/SiteService.php
- app/helpers.php
- config/modules.php
- resources/css/app.css
- resources/js/app.js
- resources/views/admin/index.blade.php
- resources/views/admin/layout.blade.php
- resources/views/admin/login.blade.php
- resources/views/admin/settings.blade.php
- resources/views/frontend/layout.blade.php
- routes/frontend.php
- routes/web.php
- tests/Feature/AdminUpdatesTest.php
- tests/Feature/CmsTest.php
- tests/Feature/PublisherTest.php
- tests/Feature/PublishingEditorTest.php
- tests/Feature/SocialPreviewTest.php

The supplied public/images/logo/logo.png and logo.ico are used directly.
