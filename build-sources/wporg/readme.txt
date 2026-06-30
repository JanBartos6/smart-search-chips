=== Smart Search Chips ===
Contributors: honzdev
Tags: woocommerce, product search, search filter, ajax search, filter
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.24
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Dynamic WooCommerce and WordPress search filter chips for products, categories, tags, attributes, and content-heavy sites.

== Description ==

Smart Search Chips adds dynamic filter chips to WordPress and WooCommerce search, so visitors can narrow search results by categories, tags, product attributes, and search context instead of getting a flat list of results.

It is built for WooCommerce stores, product catalogs, documentation sites, directories, listing sites, and content-heavy WordPress sites where normal search is not enough. Use the included Search block or `[sschp_search]` shortcode for a reliable search UI, or enhance compatible theme AJAX searches when you want to keep an existing layout.

Example: a visitor searches "lamp" and then narrows results with chips such as LED, outdoor, 12V, brand, category, or wattage.

Unlike traditional faceted search plugins that require every filter to be designed up front, Smart Search Chips generates useful chips from the current matching results. It hides redundant chips, supports a local search index, and includes diagnostics that show which search path was used and why chips did or did not appear.

**How it works:**

1. A visitor searches through the Smart Search Chips block, shortcode, or a compatible enhanced theme search
2. The plugin finds results through the local index, Relevanssi, or WordPress fallback search
3. It analyzes the matching results, Search Chips Tags, and optional WooCommerce helper tags
4. It displays clickable chips ranked by frequency and usefulness
5. Redundant chips are hidden when they would not narrow the result set
6. Clicking a chip filters the visible results without asking the visitor to rewrite the query

**Included in the WordPress.org/free edition:**

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

**Available separately in Smart Search Chips Pro:**

* Use existing ACF/custom fields as chip sources instead of manually tagging every item
* Add numeric range chips for values like 100 W, 200 W, dimensions, or mapped numeric fields
* Normalize equivalent catalog terms with synonym rules
* Control chip labels, pinned chips, hidden chips, and display rules
* Track zero-result searches, chip clicks, result clicks, impressions, and search-to-result-click rates
* Send scheduled local analytics email digests to configured site recipients
* Build external AI tagging prompts and import/export chip rules as JSON

**Works best with:**

* WooCommerce stores with many similar products
* Product catalogs using attributes such as size, color, wattage, voltage, brand, model, material, or use case
* Technical catalogs and parts/model-number sites
* Documentation sites and knowledge bases
* Directories, listing sites, resource libraries, and internal portals
* Agencies building search-heavy WordPress sites for clients

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

= Is this a hosted SaaS search service? =

No. Smart Search Chips runs as a WordPress plugin on your own site. The WordPress.org edition does not require an external search service, hosted account, or subscription, and visitor search queries are not sent to a third-party search service.

= Do I need Relevanssi? =

No. Smart Search Chips works on its own using its search block/shortcode, local search index, and fallback relevance scoring. Relevanssi is optional and recommended for sites that want stronger WordPress search relevance, especially content-heavy sites.

= How do I populate the tag fields? =

Use the built-in Search Chips Tags meta box on each post/product. Tags should be semicolon-separated. ACF/custom field connectors are available separately in Smart Search Chips Pro for teams that already maintain structured product fields.

= Can I override the search result HTML? =

Yes. Create a file at `your-theme/smart-search-chips/results.php` and the plugin will use it instead of the default template. The variable `$sschp_posts` contains the post data array.

= How does redundancy elimination work? =

The algorithm ranks tags by frequency, then for each candidate chip checks if it appears in ALL results of any already-selected chip's group. If so, it's redundant and hidden. This ensures only truly differentiating tags are shown.

= Can I use this with WooCommerce? =

Yes. Set "Post Types" to include "product". This WordPress.org edition can use the built-in Search Chips Tags field and WooCommerce helper tags from attributes, categories, and product tags. SKU chips are optional and disabled by default. ACF and regular custom field mapping is available separately in Smart Search Chips Pro.

= What is available separately in Smart Search Chips Pro? =

Smart Search Chips Pro is a separate add-on for sites that need to reuse existing ACF/custom fields, add numeric range chips, normalize catalog language with synonyms, control chip labels and rules, track zero-result and low-engagement searches, send scheduled local reports, and export/import chip rules.

== Screenshots ==

1. WooCommerce-style product search for "backpack" with dynamic filter chips, selected refinements, result counts, prices, and product cards.
2. Smart Search Chips Pro analytics overview with local reporting, export actions, filters, KPI cards, and search funnel metrics.
3. Smart Search Chips Pro analytics details with recommendation cards, zero-result searches, chip CTR, result-click tracking, and query/chip tables.

== Changelog ==

= 1.1.24 =
* Fixed chip ordering so selected chips stay immediately after numeric range chips instead of drifting behind ordinary suggestions.

= 1.1.23 =
* Reworked the WordPress.org listing copy around WooCommerce/product-search buyer intent, local-plugin positioning, and clearer Pro use cases.
* Added standard deactivation and uninstall cleanup hooks so scheduled index work is stopped and opt-in data deletion works through the normal WordPress uninstall flow.

= 1.1.22 =
* Removed visitor-facing per-result chip count badges from public search result cards while keeping summary and filter-chip counts.
* Updated release metadata for the latest public search result polish.

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

Smart Search Chips Pro is available separately from https://smartsearchchips.app/ for teams that need advanced catalog and reporting workflows:

* Reuse existing ACF/custom fields as chip sources
* Add numeric range chips for measurements and mapped numeric fields
* Normalize catalog language with synonym rules
* Pin, hide, rename, and control chip display rules
* Track zero-result searches, chip engagement, and result-click behavior
* Send scheduled local analytics digests to configured site recipients
* Import/export chip rules as JSON and use AI-assisted tagging workflows

The WordPress.org plugin is fully functional with its built-in Search Chips Tags field and does not require accounts or payment.

== Privacy Policy ==

Smart Search Chips processes search queries entered by visitors to generate dynamic filter chips. Search queries are processed on this server and are not stored or sent to any third-party service by this WordPress.org edition. Full policy: https://smartsearchchips.app/privacy-policy/

== Upgrade Notice ==

= 1.1.24 =
Keeps selected chips pinned immediately after numeric range chips in frontend search results.

= 1.1.23 =
Improves marketplace positioning and hardens deactivation/uninstall cleanup for scheduled index work and optional data deletion.

= 1.1.22 =
Removes public per-result chip count badges from search result cards while keeping useful search summary and chip counts.

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
