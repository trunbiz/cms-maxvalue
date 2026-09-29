<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class PublicationPagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['publisher_name' => '', 'contact_email' => '', 'site_url' => '', 'adsense_publisher_id' => '', 'consent_reviewed' => '0'] as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
        $pages = [
            'about' => ['About', 'Learn about our reading publication and the ideas behind our articles and stories.', <<<'HTML'
<p>[[site_name]] is a place for readers to discover stories, explore ideas, and make room for thoughtful reading. This website is published by [[publisher_name]].</p>
<h2>What you will find here</h2>
<p>Our focus is reading: the books and stories that stay with us, the questions they raise, and the habits that help us read with attention. Articles offer a complete discussion of their subject. Stories are organized into clear chapter lists so readers can follow them at their own pace.</p>
<h2>Our approach</h2>
<p>We aim to make every page useful on its own. We distinguish personal interpretation from verifiable facts, credit sources when we rely on them, and respect the rights of authors, artists, and publishers. A short summary is not a substitute for an original contribution.</p>
<p>Reading preferences such as text size, theme, and progress can be saved on your device. You do not need an account to read the public website.</p>
<h2>Accountability</h2>
<p>Questions, corrections, and feedback can be sent to <a href="mailto:[[contact_email]]">[[contact_email]]</a>. Please include the page address and enough detail for us to understand your concern.</p>
<p>See our <a href="/pages/editorial-policy">Editorial Policy</a> for our publishing standards and our <a href="/pages/privacy-policy">Privacy Policy</a> for information about data use.</p>
HTML],
            'contact' => ['Contact', 'Contact the publisher with questions, editorial feedback, corrections, and rights concerns.', <<<'HTML'
<p>For questions about [[site_name]], contact [[publisher_name]] at <a href="mailto:[[contact_email]]">[[contact_email]]</a>.</p>
<h2>Editorial feedback and corrections</h2>
<p>Include the article title, its full URL, the passage you are writing about, and any supporting sources. We welcome clear explanations of factual errors, broken links, and accessibility problems.</p>
<h2>Copyright and permissions</h2>
<p>If you believe material on this website infringes your rights, identify the original work, the exact URL in question, your relationship to the rights holder, and a way to contact you. Please read our <a href="/pages/copyright">Copyright &amp; Corrections</a> page for further details.</p>
<h2>Privacy questions</h2>
<p>Use the same email address for questions about personal information. Describe your request, but do not send passwords, payment details, identity documents, or other sensitive information unless a secure and necessary verification process has been agreed.</p>
<h2>Before you write</h2>
<p>This website does not offer public account registration, reader comments, or a mailing-list signup. If your request concerns an advertising provider or another linked website, you may also need to contact that provider directly. We cannot manage accounts or preferences held by other services.</p>
HTML],
            'privacy-policy' => ['Privacy Policy', 'How this website handles reading preferences, technical data, correspondence, and advertising cookies.', <<<'HTML'
<p>This notice explains how [[publisher_name]], the publisher of [[site_name]] at [[site_url]], handles information in connection with this website. For privacy questions, contact <a href="mailto:[[contact_email]]">[[contact_email]]</a>.</p>
<h2>Information used to operate the website</h2>
<p>When you request a page, the web server and infrastructure providers receive technical information such as your IP address, browser information, requested URL, and request time. This information may be used to deliver pages, diagnose errors, maintain security, and prevent abuse. Hosting and network logs may be retained according to the service configuration and operational needs.</p>
<p>The website counts article and story views. It briefly uses a hash derived from the visitor IP address and browser user agent to reduce repeated counting. This short-lived value is not a reader account. View totals are stored as aggregate counts.</p>
<h2>Information you choose to send</h2>
<p>If you email us, your message, email address, and any information you include will be handled to respond to the request and keep any necessary correspondence. Please avoid sending sensitive information that is not needed. Reader registration, comments, and newsletter signup are not provided on this website.</p>
<h2>Cookies and storage on your device</h2>
<p>Reading settings and recent reading history are stored in your browser using local storage. These features help retain your preferred text size, theme, and last-read chapter. They are not uploaded as a reading-history profile. You can remove them by clearing this website's browser storage. Your preferences will then return to their defaults.</p>
<p>Administrator sign-in uses essential session cookies. Public reading pages do not require a sign-in session. Fonts are served with the website rather than requested from a third-party font service.</p>
<h2>Google advertising</h2>
<p>If Google ads are enabled on this website, Google and participating advertising providers may use cookies or similar technologies to select, deliver, and measure advertising. Ad selection can take account of earlier visits to this website and other websites. Google's advertising cookies can support interest-based advertising from Google and its partners across the web.</p>
<p>You can manage personalized advertising preferences through <a href="https://myadcenter.google.com/">Google My Ad Center</a>. Information about participating providers and industry opt-out options is available at <a href="https://optout.aboutads.info/">YourAdChoices</a>. These choices do not necessarily stop all advertising or remove cookies already stored in your browser.</p>
<p>Learn how Google handles information from partner sites at <a href="https://policies.google.com/technologies/partner-sites">Google's partner-sites privacy information</a>, and review <a href="https://policies.google.com/privacy">Google's Privacy Policy</a>. When an advertising consent message is shown, it provides controls and information about the providers configured for this website. You can use those controls to make or revise your choices.</p>
<h2>Service providers and external links</h2>
<p>Hosting, file delivery, security, email, and advertising providers may process information needed to supply their services. Their processing locations and policies may differ from your own country. Links to other websites take you to services we do not control; review their privacy notices before sharing information.</p>
<h2>Your choices and requests</h2>
<p>You can clear browser storage, control cookies through browser settings, and use any applicable advertising consent controls. Depending on the laws that apply to you, you may also have rights to access, correct, delete, restrict, or object to certain uses of personal information. Contact us to explain your request. We may need to verify it and may be unable to identify you from aggregate statistics.</p>
<h2>Retention and changes</h2>
<p>We keep correspondence only as long as reasonably needed for the request, recordkeeping, security, or applicable obligations. Browser reading preferences remain on your device until cleared or replaced. We may update this notice when the website's practices change; the date shown on this page identifies its latest revision.</p>
HTML],
            'terms-of-use' => ['Terms of Use', 'Conditions for reading, linking to, and using material on this website.', <<<'HTML'
<p>These terms describe the use of [[site_name]], published by [[publisher_name]]. If you have a question about them, contact <a href="mailto:[[contact_email]]">[[contact_email]]</a>.</p>
<h2>Using the website</h2>
<p>You may read public pages and link to them. Please do not interfere with the website, attempt unauthorized access to the administration area, introduce malicious code, or use automated requests in a way that disrupts access for other readers.</p>
<h2>Content and permissions</h2>
<p>Articles, stories, images, and other materials may be protected by copyright or other rights. Public availability does not grant permission to republish a complete work. Obtain the necessary permission before reusing material, except where an applicable legal exception or an express license permits the use. Credits or source links do not, by themselves, create a license.</p>
<h2>Information and opinions</h2>
<p>Articles may contain opinion, interpretation, or creative fiction. Publication does not guarantee that every statement is complete or current. Use primary sources where accuracy matters and contact us about suspected errors. Content is general reading material, not a substitute for advice tailored to a legal, medical, financial, or other professional situation.</p>
<h2>Links and advertising</h2>
<p>External links and advertisements may lead to services operated by others. Their terms, availability, and privacy practices are their own responsibility. An advertisement is not an editorial endorsement. Any sponsored or affiliate material should be identified with the relevant content.</p>
<h2>Availability and updates</h2>
<p>Pages may be corrected, moved, or removed, and access may be interrupted for maintenance or other reasons. These terms may change as the website develops. Nothing in these terms is intended to exclude rights or responsibilities that cannot be excluded under applicable law.</p>
HTML],
            'editorial-policy' => ['Editorial Policy', 'Our standards for original writing, attribution, transparency, and corrections.', <<<'HTML'
<p>[[site_name]] is published by [[publisher_name]]. The following standards guide material published on this website.</p>
<h2>Original work with a clear purpose</h2>
<p>Every article should answer a real reader question, develop an idea, or offer a complete and worthwhile reading experience. Repeated filler, copied summaries, and lightly rewritten work do not meet this standard. Fiction should be identified as fiction and organized so that readers can follow the story.</p>
<h2>Accuracy and sources</h2>
<p>Check factual claims against reliable sources where appropriate. Link to the original source when possible, distinguish facts from opinions, and avoid presenting speculation as established information. Quotations should be limited, accurate, and attributed; images and other third-party material require a valid basis for use.</p>
<h2>Authorship and assistance</h2>
<p>Bylines should identify the actual writer or the responsible publication. Do not invent qualifications, experiences, interviews, or sources. If an automated tool assists with research or drafting, the person publishing the work remains responsible for reviewing its accuracy, originality, permissions, and usefulness. Material use that affects how readers understand a work should be disclosed in context.</p>
<h2>Commercial transparency</h2>
<p>Advertising must be distinguishable from editorial content. Sponsorships, affiliate relationships, and other material commercial connections should be disclosed clearly with the relevant content. We do not ask readers to click advertisements to support the site.</p>
<h2>Corrections</h2>
<p>Readers can report errors to <a href="mailto:[[contact_email]]">[[contact_email]]</a>. Include the page URL and supporting information. Substantive corrections should be explained on the affected page; a change to formatting or spelling does not necessarily require a separate correction notice.</p>
HTML],
            'copyright' => ['Copyright & Corrections', 'How to request permission, report a copyright concern, or suggest a correction.', <<<'HTML'
<p>For permission requests, copyright concerns, or corrections relating to [[site_name]], contact [[publisher_name]] at <a href="mailto:[[contact_email]]">[[contact_email]]</a>.</p>
<h2>Reusing material</h2>
<p>Unless a page states otherwise, do not assume that its text, images, or story chapters are free to republish. Identify the material and the use you have in mind when requesting permission. Some material may belong to another rights holder, whose permission you will need separately.</p>
<h2>Reporting a rights concern</h2>
<p>Please provide the title or description of the original work, the exact URL of the material on this website, an explanation of the rights involved, your relationship to the rights holder, and contact details. Include supporting links or documentation where useful. Send only information necessary to assess the concern.</p>
<p>We will assess the material identified and may seek clarification, correct attribution, restrict access, or remove material as appropriate. This contact procedure does not replace any formal notice process or rights available under applicable law.</p>
<h2>Correcting an error</h2>
<p>For a factual correction, include the relevant passage and a reliable source explaining the issue. For a broken link or display problem, include the page URL and a description of what happened. See the <a href="/pages/editorial-policy">Editorial Policy</a> for our approach to substantive corrections.</p>
HTML],
        ];
        $footer = Menu::firstOrCreate(['slug' => 'footer'], ['name' => 'Footer navigation']);
        foreach ($pages as $slug => [$title,$description,$content]) {
            $page = Page::firstOrCreate(['slug' => $slug], ['title' => $title, 'content' => $content, 'seo_title' => $title, 'seo_description' => $description, 'status' => 'draft']);
            if ($page->content === '') {
                $page->update(['content' => $content, 'seo_title' => $title, 'seo_description' => $description, 'status' => 'draft']);
            }
            if (! $footer->items()->where('type', 'page')->where('target_id', $page->id)->exists()) {
                $footer->items()->create(['label' => $title, 'type' => 'page', 'target_id' => $page->id, 'sort_order' => count($pages) + $page->id]);
            }
        }
        $main = Menu::firstOrCreate(['slug' => 'main'], ['name' => 'Main navigation']);
        foreach (['Home' => '/', 'Articles' => '/articles'] as $label => $url) {
            if (! $main->items()->where('type', 'url')->where('url', $url)->exists()) {
                $main->items()->create(['label' => $label, 'type' => 'url', 'url' => $url, 'sort_order' => $url === '/' ? 0 : 1]);
            }
        }
    }
}
