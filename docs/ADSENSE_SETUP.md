# Publishing in English and preparing an AdSense application

The CMS, reader interface, editor, notifications, and canonical URL prefixes are in English. The website is ready for you to write and preview articles. An AdSense application still needs a live public domain, accurate publisher details, reviewed policies, and useful original content. Code and policy pages alone do not establish eligibility or guarantee approval.

## 1. Complete the publisher profile

Open **Admin → Settings** and enter:

- Website name.
- Actual publisher/operator name.
- A working public contact email you control.
- Production website origin, such as `https://your-domain.com`.
- A description that accurately explains the publication.

No publisher identity or email has been invented. The initial brand is **Reading Corner** until you change it.

## 2. Review the six prepared pages

These drafts are available under **Admin → Pages**:

| Page | Public URL after publication |
| --- | --- |
| About | `/pages/about` |
| Contact | `/pages/contact` |
| Privacy Policy | `/pages/privacy-policy` |
| Terms of Use | `/pages/terms-of-use` |
| Editorial Policy | `/pages/editorial-policy` |
| Copyright & Corrections | `/pages/copyright` |

The templates use `[[site_name]]`, `[[publisher_name]]`, `[[contact_email]]`, and `[[site_url]]`. These values are escaped and filled from Settings when rendered. You can preview saved pages privately before publishing. Draft pages return 404 publicly and are excluded from footer links and the sitemap.

Read and adapt the text to your actual practices, providers, content rights, and audience. For example, adding analytics, email subscriptions, comments, or new advertising vendors requires a corresponding privacy-policy review. The templates are a starting point, not a certification of legal compliance.

After reviewing, either publish pages individually or use **Publish reviewed pages** in Settings. Publisher details must be complete first. Drafting About, Contact, Terms, and editorial pages helps readers understand the site; these pages are not a guaranteed list of Google approval requirements.

## 3. Write and publish real articles

1. Open **Admin → Articles & stories → Add new**.
2. Choose **Standard article**.
3. Add a specific title, a useful summary, the article, categories and tags. Use only images you are entitled to use.
4. Save as **Draft**, then click **Preview saved content**. The preview requires admin permission, is not cached, and is marked noindex.
5. Check sources, formatting, image rights, original contribution, and links. Publish when ready. A future publication date keeps the article hidden until that date.

Articles display publication/update dates, estimated reading time, related articles, a copy-link button, canonical metadata, and Article JSON-LD. Author names are not collected in the editor or exposed in article markup.

The original 155 unchanged sample posts/chapters were converted into English **drafts**. They are not an AdSense content collection. The preparation command leaves authored content and edited policy pages unchanged. Rewriting a demo article clears its demo marker when saved.

There is no hard-coded article count, word count, or traffic number that is presented as a guarantee of approval. The setup screen only checks whether original articles have been published, not whether their quality is sufficient.

## 4. Deploy to the real domain

- Point the web server document root to `public/` and configure HTTPS.
- Set `APP_URL` to the real domain, matching the URL in Settings.
- Set `APP_ENV=production`, `APP_DEBUG=false`, and configure MySQL, Redis, media storage, queue, and scheduler.
- Run `composer install --no-dev`, `npm ci`, `npm run build`, `php artisan migrate --force`, and `php artisan config:cache`.
- Confirm `/articles`, the six policy pages, `/robots.txt`, and `/sitemap.xml` can be reached without a login.
- Public URLs must remain accessible to Google's verification crawlers; do not place a login or bot challenge in front of them.

Local preview URLs are marked noindex. Public search results and empty listing pages are also marked noindex. Published content appears in the sitemap. Old Vietnamese route prefixes redirect permanently to their English equivalents.

For an existing installation that has not received the English preparation step:

```bash
php artisan migrate
php artisan site:prepare-english
```

This command is idempotent and does not reset the database. On a new empty database, `php artisan migrate --seed` supplies English demo drafts and the policy drafts. Do not use `migrate:fresh` against your live articles.

## 5. Connect your AdSense account

In **Admin → Settings**, enter your own Publisher ID in the format `ca-pub-` followed by 16 digits. Saving it:

- Outputs the `google-adsense-account` verification meta tag on public pages.
- Adds the corresponding Google DIRECT entry to `/ads.txt`.
- Does **not** load ad scripts, place advertisements, or submit an application.

Without a Publisher ID or manually supplied ads.txt content, `/ads.txt` returns 404 instead of a fabricated account entry. Additional authorized sellers can be entered in the ads.txt field. Avoid duplicate or conflicting entries for your own Google publisher account.

Sign in to AdSense, add the production domain under **Sites → New site**, choose the meta-tag or ads.txt verification method, verify ownership, and request review. Review and approval remain Google's decision.

## 6. Consent before serving ads

Before placing ad scripts in the Super Admin-only head HTML field, configure the consent messages appropriate for your audience and providers. For relevant EEA, UK, and Swiss traffic, follow Google's certified CMP requirements. Use AdSense **Privacy & messaging** or an appropriate Google-certified CMP; a custom accept/reject banner alone is not a replacement.

The Settings confirmation records your own review. It does not implement a CMP or verify its configuration. The privacy draft describes Google advertising conditionally because no ad scripts are enabled by default.

## Official references

- [Google: prepare site content and navigation](https://support.google.com/adsense/answer/7299563?hl=en).
- [Google: required privacy disclosures](https://support.google.com/adsense/answer/1348695?hl=en).
- [Google: ownership verification and requesting review](https://support.google.com/adsense/answer/12169212?hl=en).
- [Google: consent management requirements for publishers](https://support.google.com/adsense/answer/13554116?hl=en).

These sources were checked during implementation. Refer to the current instructions in your AdSense account when submitting.
