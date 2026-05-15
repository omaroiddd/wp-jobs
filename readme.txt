=== Apqrinu Job Board ===
Contributors:      apqrinu
Tags:              jobs, job board, careers, hiring, jobposting
Requires at least: 6.0
Tested up to:      6.9
Requires PHP:      7.4
Stable tag:        1.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

A lightweight job board: custom post type, taxonomy filters, AJAX listing, related jobs, built-in apply form, and JobPosting structured data.

== Description ==

Apqrinu Job Board is a lightweight, dependency-free job board plugin. It registers a "Job" post type with four taxonomies (job type, work mode, experience level, location), a clean archive with AJAX filtering and pagination, related-jobs pagination on single jobs, a built-in AJAX apply form (with optional resume upload), and JobPosting JSON-LD for SEO.

Source code, issues, and contributions: [github.com/omaroiddd/wp-jobs](https://github.com/omaroiddd/wp-jobs).

= Features =

* Custom post type **Job** with native meta fields (no ACF dependency).
* Taxonomies: Job Type, Work Mode, Experience Level, Location.
* Frontend archive with AJAX filtering, pushState URLs, and bookmarkable pagination.
* Related jobs section on single job pages with AJAX pager.
* Built-in apply form with AJAX submission, honeypot anti-spam, optional resume upload, and admin email notification.
* Drop-in support for any third-party form plugin (Fluent Forms, WPForms, Contact Form 7, Gravity Forms, Forminator, etc.) via a per-job or global "Apply Form Shortcode" setting.
* Per-job override email and external URL.
* Applications stored as a private post type for easy review.
* JobPosting JSON-LD on single job pages.
* Theme template overrides via `your-theme/apqrinu-job-board/`.
* Three shortcodes: `[apqrinu_listings]`, `[apqrinu_related]`, `[apqrinu_apply_form]`.
* Settings page for per-page count, currency, default email, expired-job hiding.
* Color picker for primary, hover, text, card, border, and meta colors — applied site-wide via CSS variables.
* Toggles for showing or hiding the related-jobs section, and for hiding the section entirely when no similar jobs are found.
* Modal-only apply experience with a blurred backdrop and full-screen layout on mobile.
* Translation-ready with text domain `apqrinu-job-board`. Ships a `wpml-config.xml` that registers the Job CPT, all four taxonomies, and the per-job meta with WPML / Polylang for full multilingual support.

= Shortcodes =

* `[apqrinu_listings]` — full listings with filters and pagination.
* `[apqrinu_related job_id="123"]` — related jobs (defaults to current single job).
* `[apqrinu_apply_form job_id="123"]` — apply form for the given job.

= Theme overrides =

Copy any file from `wp-content/plugins/apqrinu-job-board/templates/` to `wp-content/themes/your-theme/apqrinu-job-board/` to override it. For example: `apqrinu-job-board/parts/job-card.php`.

== Installation ==

1. Upload the `apqrinu-job-board` folder to `/wp-content/plugins/`.
2. Activate **Apqrinu Job Board** through the **Plugins** menu in WordPress.
3. Go to **Jobs → Settings** to configure per-page count, currency, and notification email.
4. Add jobs via **Jobs → Add New**.

== Frequently Asked Questions ==

= Does this plugin require ACF or any page builder? =

No. All custom fields are native WordPress meta with a built-in metabox. There are no third-party dependencies.

= Where do submitted applications go? =

Each application is stored as a private `apqrinu_application` post (visible under **Jobs → Applications**) and an email is sent to the per-job address, the global default, or the site admin email — in that order.

= Can I link to an external application page instead? =

Yes. Set "External Application URL" on the job. The Apply button will link to it directly and the built-in form will be skipped.

= Can I use Fluent Forms / WPForms / Contact Form 7 / Gravity Forms instead of the built-in form? =

Yes. There are two ways:

1. **Per job**: open the job in the editor, scroll to **Job Details → Apply Form Shortcode**, and paste your form shortcode (e.g. `[fluentform id="3"]` or `[wpforms id="123"]`). The built-in form will be replaced with that shortcode for that job only.
2. **Site-wide default**: go to **Jobs → Settings → Default Apply Form Shortcode** and paste a shortcode to use for every job that doesn't define its own.

The priority order is: External URL → per-job shortcode → global default shortcode → built-in form.

= How do I customize the templates? =

Copy a file from `templates/` in the plugin to `your-theme/apqrinu-job-board/` keeping the same relative path.

= Is the plugin compatible with WPML / Polylang? =

Yes. The plugin ships a `wpml-config.xml` at its root that registers the **Job** post type and the four job taxonomies (Job Type, Work Mode, Experience Level, Location) as translatable. Job summary, company name, and the per-job apply-form shortcode are marked as translatable strings; numeric and date meta (deadline, salaries, status, application URL) are copied across translations. Dynamic plugin options (default applications email, default apply-form shortcode, currency code, currency symbol) are exposed to WPML's String Translation UI under the `apqrinu_settings` admin-text key.

== Screenshots ==

1. The frontend listings page with filters.
2. A single job with sidebar, content, and apply form.
3. The Job Details metabox.
4. The Settings page.

== Privacy ==

When a visitor submits the built-in apply form, Apqrinu Job Board stores the following data on this site:

* Applicant name, email address, phone number (optional), and cover letter message — saved as private posts of the `apqrinu_application` custom post type, visible only to administrators.
* Resume file (optional) — uploaded to the WordPress Media Library and attached to the application record.
* Submitter IP address — saved with each application for spam moderation purposes.

In addition, an email notification containing the same data (and the resume as an attachment, when provided) is sent to the address configured under **Jobs → Settings → Default applications email**, or to the per-job override email if set, falling back to the site administrator email.

The plugin does not send any data to third-party services, does not load remote scripts or fonts, and does not set tracking cookies. Application records and uploaded resumes can be deleted at any time from **Jobs → Applications**, and uninstalling the plugin removes all stored applications, plugin options, and orphaned job meta (see `uninstall.php`).

If your site is subject to GDPR, CCPA, or similar regulations, you are responsible for adding an appropriate disclosure to your privacy policy and for honoring deletion / export requests for the data above.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
First public release.
