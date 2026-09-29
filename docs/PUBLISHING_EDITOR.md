# Publishing editor

The editor groups fields into Content, Organization, Featured image, Publishing, and Search & link. The layout uses a main writing area and a settings sidebar, stacking on smaller screens.

## Articles

1. Open `/admin/posts/create` and choose **Standard article**.
2. Write the title, summary and body. CKEditor supports headings, bold, italic, underline, strikethrough, lists, quotes, links, tables, undo/redo and image upload from a device, clipboard or drag-and-drop.
3. Search categories and tags, then tick the desired boxes. Multiple categories and tags are supported; filtering does not deselect hidden choices. A new tag can be added without leaving the form.
4. Select a featured image to see a local preview immediately. **Undo selection** restores the saved image. Upload validation also runs on the server.
5. Choose Draft or Published, then save. Use **Preview saved content** to review a saved draft. **Copy link** is available in the article list, edit screen, and public article page; draft public links become accessible after publication.

Author names are no longer collected in the form or included in the article byline/JSON-LD. Existing database values are retained without being displayed.

## Split a manuscript into chapters

1. Choose **Split text into chapters** on the same creation screen. `/admin/import` redirects here.
2. Select an existing story or leave it empty to create one. Paste the manuscript into **Intro/Description**. For a new story, the first line is its title; subsequent introductory lines form the description. Mark chapter boundaries with `CHAPTER X - Title`.
3. Set categories, tags, cover image, publication status, and any duplicate-number options.
4. Click **Analyze chapters**. The story summary and expandable, full-content chapter previews appear directly below the manuscript. Analysis does not create database posts.
5. Review the chapters. To correct them, edit the manuscript and analyze again. Changing the content or saved settings invalidates the previous preview. Filtering choice lists does not invalidate it.
6. Choose **Published** before analysis if the chapters should go live, then click **Save chapters** once. The reviewed set is saved transactionally. Draft remains available for work in progress.

There is no Single chapter creation option or manual chapter-number input. Existing chapters can still be edited; their story and chapter number are preserved by the server. Duplicate handling supports keeping or replacing existing chapters. An analyzed preview expires after one hour and can be used once by its owner.

## Validation

- `php artisan test`: application and publishing workflow Feature tests.
- `npm run test:ui`: DOM interaction checks for search, selection, image previews, copy-link feedback, stale analysis handling, and real CKEditor plugin initialization/commands.
- `npm run build`: production assets.

The DOM tests do not replace a real browser layout review. The integrated browser was unavailable during this update, so visual verification was not completed.

## Deployment

Run `php artisan migrate --force`, `npm ci`, `npm run build`, and rebuild Blade views as needed. The category migration adds pivot tables and preserves existing category assignments. Do not reset a database containing authored content.
