=== Smart Search Chips ===
Contributors: honzdev
Tags: search, filter, chips, woocommerce, product search
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.21
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Guided search chips for WordPress and WooCommerce. Add a reliable search block or shortcode, generate non-redundant chips, and debug search setup from admin.

== Description ==

Smart Search Chips adds a reliable guided search UI to WordPress. Use the included Search block or `[sschp_search]` shortcode for a guaranteed search experience, or safely enhance compatible theme AJAX searches when you want to keep an existing layout.

Unlike traditional faceted search plugins that require you to predefine every filter, Smart Search Chips generates useful chips from the matching results. It hides redundant tags, supports a local search index, and includes admin diagnostics so you can see why chips did or did not appear.

**How it works:**

1. A visitor searches through the Smart Search Chips block, shortcode, or a compatible enhanced theme search
2. The plugin finds results through the local index, Relevanssi, or WordPress fallback search
3. It analyzes the matching results and their Search Chips Tags
4. It displays clickable chips ranked by frequency and usefulness
5. Redundant chips are hidden when they would not narrow the result set
6. Clicking a chip filters the visible results without asking the visitor to rewrite the query

**Key Features:**

* Search block and `[sschp_search]` shortcode for a guaranteed search UI
* Dynamic chip generation from Search Chips Tags on matching results
* Smart redundancy elimination so chips that do not narrow results stay hidden
* Admin Test Search tool showing search path, result counts, chips, warnings, and sample results
* "Why no chips?" explanations for empty results, missing tags, redundant tags, index readiness, and common WooCommerce setup issues
* Local search index with queue, rebuild, process next batch, pause, resume, and clear controls
* Works with Relevanssi (recommended) for relevance-based scoring
* WP_Query fallback when Relevanssi or the local index is not available
* Built-in Search Chips Tags field for posts, pages, and products
* Optional WooCommerce helper tags from product attributes, categories, and product tags
* Live search endpoint with instant chip filtering
* Fully customizable colors, limits, and thresholds
* Safely enhance existing theme AJAX search handlers when compatible
* Theme template overrides for custom result rendering

**Works best with:**

* Product catalogs with many similar items
* Technical documentation sites
* Any site where search results need smart filtering

== Installation ==

1. Upload the `smart-search-chips` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to Search Chips in the WordPress admin sidebar to configure
4. Add Search Chips Tags to your posts/products using the plugin meta box
5. Select which post types to search
6. Optionally install Relevanssi (free) for better search ranking

**Quick Start:**

1. Add semicolon-separated tags to the built-in Search Chips Tags field on your posts/products
2. Select which post types to search in Search Chips
3. For the most reliable setup, add the Smart Search Chips Search block, or add `[sschp_search]` to a Shortcode block
4. Optional: use "Override AJAX Action" only when you want to enhance a compatible existing theme search
5. Use Search Chips > Start Here to run a Test Search and confirm the path, chips, and warnings

== Frequently Asked Questions ==

= Do I need Relevanssi? =

No, but it's recommended. Without Relevanssi, the plugin uses default WordPress search. With Relevanssi, you get relevance-based scoring which makes the chip generation smarter (irrelevant results are filtered out before chips are calculated).

= How do I populate the tag fields? =

Use the built-in Search Chips Tags meta box on each post/product. Tags should be semicolon-separated. ACF/custom field connectors are available separately in Smart Search Chips Pro as a Pro add-on feature for teams that already maintain structured product fields.

= Can I override the search result HTML? =

Yes. Create a file at `your-theme/smart-search-chips/results.php` and the plugin will use it instead of the default template. The variable `$sschp_posts` contains the post data array.

= How does redundancy elimination work? =

The algorithm ranks tags by frequency, then for each candidate chip checks if it appears in ALL results of any already-selected chip's group. If so, it's redundant and hidden. This ensures only truly differentiating tags are shown.

= Can I use this with WooCommerce? =

Yes. Set "Post Types" to include "product". This WordPress.org edition can use the built-in Search Chips Tags field and WooCommerce helper tags from attributes, categories, and product tags. SKU chips are optional and disabled by default. ACF and regular custom field mapping is available separately in Smart Search Chips Pro.

= What is available separately in Smart Search Chips Pro? =

Smart Search Chips Pro is a separate add-on for advanced workflows such as ACF/custom field mapping, numeric range slider chips, local search analytics/reporting, synonym rules, manual chip curation, AI-assisted tag workflows, commercial support, and authenticated REST access for managing Pro chip rules.

== Screenshots ==

1. Guaranteed search UI rendered by the Search block or shortcode, with generated chips and result previews from a local demo WordPress site.
2. Admin Test Search reports the search path, result counts, generated chips, warnings, and first results.
3. Local Search Index controls show readiness, progress, indexed rows, and maintenance actions.

== Changelog ==

= 1.1.21 =
* Updated release metadata for the latest Smart Search Chips support-contact correction.

= 1.1.20 =
* Replaced public support and feedback mailto links with support@smartsearchchips.app.
* Updated launch-site legal/support pages to use the public support address.

= 1.1.19 =
* Moved Smart Search Chips into a top-level WordPress admin sidebar menu with redirects from the old Settings URL.
* Improved frontend chip/result count badges and animated count updates in the guaranteed search UI.
* Updated setup guidance to point administrators to the new Search Chips sidebar menu.

= 1.1.18 =
* Strengthened release validation with expanded CI and browser/integration coverage.
* Added a manual release smoke checklist for email delivery, WordPress.org packaging, and live-site validation.

= 1.1.17 =
* Updated admin polish and release metadata for the latest Smart Search Chips improvements.

= 1.1.16 =
* Reorganized the admin settings screen into sticky sections for setup, search, chips, index, diagnostics, contextual Pro workflows, and advanced maintenance.

= 1.1.15 =
* Updated release metadata for the latest Pro numeric range chip fix.

= 1.1.14 =
* Hydrates bare guaranteed-search shortcode text into the live search UI when a page builder or theme prints the shortcode literally.

= 1.1.13 =
* Improved the Guaranteed Search Mode setup card layout at medium admin widths.

= 1.1.12 =
* Updated release metadata for the latest Pro numeric range parsing fix.

= 1.1.11 =
* Updated release metadata for the latest Pro numeric range fix.

= 1.1.10 =
* Updated release metadata for the latest Pro numeric range improvements.

= 1.1.9 =
* Added a legacy shortcode alias so older demo/theme content renders the guaranteed search UI instead of shortcode text.

= 1.1.8 =
* Updated release metadata for the latest Pro numeric range improvements.

= 1.1.7 =
* Clarified Guaranteed Search Mode setup guidance so the block path and shortcode fallback are shown as separate choices.

= 1.1.6 =
* Refined control styling for desktop pointer alignment.

= 1.1.5 =
* Improved admin settings panel stacking at narrower widths.

= 1.1.4 =
* Improved admin settings layout wrapping at narrower widths.

= 1.1.3 =
* Updated release automation to publish verified builds immediately.

= 1.1.2 =
* Tightened release automation and deployment secret validation.

= 1.1.1 =
* Fixed automated release packaging and tightened version-tag deployment checks.

= 1.1.0 =
* Added CI coverage for unit tests, WP.org packaging validation, linting, and browser compatibility checks.
* Added the Search Debug admin tool with search-path reporting, warnings, generated chips, and sample results.
* Added reusable "why no chips?" explanations for empty results, redundant tags, missing tags, index readiness, WooCommerce settings, and theme selector issues.
* Added local search index controls for rebuilding, processing one batch, clearing, pausing, and resuming indexing.
* Added the Smart Search Chips Gutenberg search block for the guaranteed search UI.
* Improved WP.org package validation to keep premium-only code out of the free build.

= 1.0.15 =
* Added first-run setup guidance with a guaranteed shortcode/block integration path.
* Split shortcode search from optional theme AJAX enhancement so the shortcode always uses the built-in endpoint.
* Made theme AJAX compatibility settings available without Pro gating.
* Added configurable search input selector, keyword field, result container, and result image handling to the compatibility settings.
* Hardened internal feature checks to avoid direct-install fatals when optional billing components are unavailable.
* Added contextual, admin-only Smart Search Chips Pro information cards for relevant advanced workflows.

= 1.0.14 =
* Added factual admin-only Smart Search Chips Pro links on the plugin row and relevant settings sections.
* Clarified contextual Pro messaging without disabled controls or blocked setup steps.

= 1.0.13 =
* Restored chip generation to use the currently filtered result set after chip selection.
* Removed the forced AJAX result thumbnail display size by default and added an optional pixel override.

= 1.0.12 =
* Fixed theme search close buttons leaving an empty active results strip.
* Keeps chip suggestions available from the base search set after a chip is selected.
* Increased the default AJAX result image display size.

= 1.0.6 =
* Improved chip-click behavior inside third-party AJAX search panels so selecting a chip keeps the search UI open.
* Improved frontend input discovery for theme search forms that use keyword, s, or query fields.

= 1.0.5 =
* Added runtime WooCommerce tag fallback so chips can appear from product attributes, categories, and product tags before generated helper tags are stored. SKU chips are optional and disabled by default.

= 1.0.4 =
* Fixed post type settings persistence when selecting products or other custom post types.
* Added an extra save button below the settings style preview.

= 1.0.0 =
* Initial release
* Core chip engine with redundancy elimination
* Relevanssi integration with relevance threshold
* AJAX search handler with chip filtering
* Admin settings page
* Shortcode [sschp_search]
* Theme template override support

== Smart Search Chips Pro ==

Smart Search Chips Pro is available separately from https://smartsearchchips.app/ for teams that want advanced workflows: ACF/custom field connectors, numeric range slider chips, local search analytics/reporting, synonym rules, manual chip curation, AI-assisted tag workflows, commercial support, and authenticated REST access for Pro chip rules. The WordPress.org plugin is fully functional with its built-in Search Chips Tags field and does not require accounts or payment.

== Privacy Policy ==

Smart Search Chips processes search queries entered by visitors to generate dynamic filter chips. Search queries are processed on this server and are not stored or sent to any third-party service by this WordPress.org edition. Full policy: https://smartsearchchips.app/privacy-policy/

== Upgrade Notice ==

= 1.1.21 =
Updates release metadata for the latest Smart Search Chips support-contact correction.

= 1.1.20 =
Uses the public support email for admin feedback and website support links.

= 1.1.19 =
Moves Search Chips into a top-level admin menu and improves frontend search count polish.

= 1.1.18 =
Strengthens release validation for the latest Smart Search Chips launch-hardening work.

= 1.1.17 =
Updates admin polish and release metadata for the latest Smart Search Chips improvements.

= 1.1.16 =
Makes the settings page easier to navigate by grouping setup, diagnostics, local index controls, and advanced maintenance into a sticky section nav.

= 1.1.15 =
Updates release metadata for the latest Pro numeric range chip fix.

= 1.1.14 =
Restores the live guaranteed-search UI when a theme or builder outputs the search shortcode as plain text.

= 1.1.13 =
Improves the Guaranteed Search Mode setup card layout at medium admin widths.

= 1.1.12 =
Updates release metadata for the latest compatibility parsing fix.

= 1.1.11 =
Updates release metadata for the latest compatibility fix.

= 1.1.10 =
Updates release metadata for the latest compatibility improvements.

= 1.1.9 =
Restores older legacy shortcode embeds by routing them to the guaranteed Smart Search Chips search UI.

= 1.1.8 =
Updates release metadata for the latest compatibility improvements.

= 1.1.7 =
Clarifies Guaranteed Search Mode setup guidance.

= 1.1.6 =
Refines control styling for desktop pointer alignment.

= 1.1.5 =
Improves admin settings panel stacking at narrower widths.

= 1.1.4 =
Improves admin settings layout wrapping at narrower widths.

= 1.1.3 =
Updates release automation for immediate verified deployment.

= 1.1.2 =
Tightens release automation and deployment secret validation.

= 1.1.1 =
Fixes automated release packaging and keeps deployments tied to matching version tags.

= 1.1.0 =
Adds release-ready diagnostics, local index controls, the guaranteed search block, and stronger WP.org package validation.

= 1.0.15 =
Adds a guaranteed shortcode setup path, improves theme live-search compatibility settings, and keeps Pro information contextual/admin-only.

= 1.0.14 =
Adds factual admin-only Pro feature links in relevant settings locations.

= 1.0.13 =
Restores filtered-set chip generation and makes AJAX result thumbnail display sizing opt-in.

= 1.0.12 =
Improves live-search close behavior, chip continuity after selection, and default result image size.

= 1.0.6 =
Keeps live search panels open when visitors click chips in theme autocomplete integrations.

= 1.0.5 =
Improves WooCommerce chip generation for existing product catalogs.

= 1.0.4 =
Fixes post type settings saving for product catalogs.

= 1.0.0 =
Initial release.
