=== Smart Search Chips ===
Contributors: honzdev
Tags: woocommerce, product search, search filter, ajax search, filter
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.30
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Dynamic WooCommerce and WordPress search filter chips for products, categories, tags, attributes, and content-heavy sites.

== Description ==

Smart Search Chips adds dynamic filter chips to WordPress and WooCommerce search, so visitors can narrow search results by categories, tags, product attributes, and search context instead of getting a flat list of results.

It is built for WooCommerce stores, product catalogs, documentation sites, directories, listing sites, and content-heavy WordPress sites where normal search is not enough. Use the included Search block or `[sschp_search]` shortcode for a reliable search UI, or enhance compatible theme AJAX searches when you want to keep an existing layout.

Example: a visitor searches "lamp" and then narrows results with chips such as LED, outdoor, 12V, brand, category, or wattage range.

Unlike traditional faceted search plugins that require every filter to be designed up front, Smart Search Chips generates useful chips from the current matching results. It hides redundant chips, supports a local search index, and includes diagnostics that show which search path was used and why chips did or did not appear.

**How it works:**

1. A visitor searches through the Smart Search Chips block, shortcode, or a compatible enhanced theme search
2. The plugin finds results through the local index, Relevanssi, or WordPress fallback search
3. It analyzes the matching results, Search Chips Tags, optional WooCommerce helper tags, and Pro field mappings when enabled
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
* Product category archive chips that compare the full category and appear above compatible product lists
* Live search endpoint with instant chip filtering
* Legacy `[ssc_search]` shortcode alias for older embeds
* Fully customizable colors, limits, and thresholds
* Safely enhance existing theme AJAX search handlers when compatible
* Theme template overrides for custom result rendering

**Available separately in Smart Search Chips Pro:**

* Use existing ACF/custom fields as chip sources instead of manually tagging every item
* Add numeric range chips for values like 100 W, 200 W, dimensions, or mapped numeric fields
* Link paired numeric values with the inline ACF/text marker `=>`, for example 690 V => 63 A
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
4. Add Search Chips Tags to your posts/products, or map existing custom fields in Pro
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

Free uses the built-in Search Chips Tags field. Pro can map existing ACF or regular WordPress custom fields and adds AI-assisted tagging workflows for larger catalogs.

= Can I override the search result HTML? =

Yes. Create a file at `your-theme/smart-search-chips/results.php` and the plugin will use it instead of the default template. The variable `$sschp_posts` contains the post data array.

= How does redundancy elimination work? =

The algorithm ranks tags by frequency, then for each candidate chip checks if it appears in ALL results of any already-selected chip's group. If so, it's redundant and hidden. This ensures only truly differentiating tags are shown.

= Can I use this with WooCommerce? =

Yes. Set "Post Types" to include "product". Free can use the built-in Search Chips Tags field and WooCommerce helper tags from attributes, categories, and product tags. SKU chips are optional and disabled by default; Pro can also map ACF or regular custom fields.

= What is available separately in Smart Search Chips Pro? =

Smart Search Chips Pro is a separate add-on for sites that need to reuse existing ACF/custom fields, add numeric range chips, normalize catalog language with synonyms, control chip labels and rules, track zero-result and low-engagement searches, send scheduled local reports, and export/import chip rules.

== Screenshots ==

1. WooCommerce-style product search for "backpack" with dynamic filter chips, selected refinements, result counts, prices, and product cards.
2. Smart Search Chips Pro analytics overview with local reporting, export actions, filters, KPI cards, and search funnel metrics.
3. Smart Search Chips Pro analytics details with recommendation cards, zero-result searches, chip CTR, result-click tracking, and query/chip tables.

== Changelog ==

= 1.1.30 =
* Added an archive filter language setting with Auto, English, and Czech options for headings, descriptions, clear-filter links, and accessible chip actions.
* Added bundled Czech translations and reliable translation loading for both Free and Pro plugin directories.

= 1.1.29 =
* Fixed chip ordering so every active chip, including selected numeric ranges, appears before every inactive chip.

= 1.1.28 =
* Fixed product category filters in custom themes that render their visible product cards through a separate secondary query.

= 1.1.27 =
* Fixed Pro numeric range controls being enlarged or restyled by theme-wide range input CSS, including oversized square slider handles.

= 1.1.26 =
* Added full-category chip generation and filtering on WooCommerce and custom product category archives, including products beyond the current pagination page.
* Added a custom-theme placement fallback so archive chips appear once, directly above the product list, when standard WordPress or WooCommerce loop hooks are unavailable.

= 1.1.25 =
* Added Pro numeric range linking with the explicit inline `=>` marker so paired values like `690 V => 63 A` create current ranges scoped to the voltage value.
* Kept plain language values like `690 V pri 63 A` unlinked so existing catalog text does not change behavior unexpectedly.

= 1.1.24 =
* Fixed chip ordering so numeric range chips stay first and selected chips appear immediately after them before ordinary suggestions.

= 1.1.23 =
* Reworked the WordPress.org listing copy around WooCommerce/product-search buyer intent, local-plugin positioning, and clearer Pro use cases.
* Added standard deactivation and uninstall cleanup hooks so scheduled plugin work is stopped and opt-in data deletion works through the normal WordPress uninstall flow.

= 1.1.22 =
* Redesigned the Pro analytics dashboard overview with clearer scorecards, funnel metrics, and data-driven recommendation cards.
* Removed visitor-facing per-result chip count badges from public search result cards while keeping summary and filter-chip counts.

= 1.1.21 =
* Restored the Freemius Contact menu for licensed customer support.
* Kept public website and admin feedback links on support@smartsearchchips.app.

= 1.1.20 =
* Replaced public support and feedback mailto links with support@smartsearchchips.app.
* Disabled the Freemius Contact menu entry to avoid exposing account-level contact metadata.
* Updated launch-site legal/support pages to use the public support address.

= 1.1.19 =
* Moved Smart Search Chips into a top-level WordPress admin sidebar menu with redirects from the old Settings URL.
* Moved Pro analytics and range diagnostics under the Search Chips admin menu for easier navigation.
* Improved frontend chip/result count badges and animated count updates in the guaranteed search UI.

= 1.1.18 =
* Strengthened release CI with PHP linting, full release builds, package artifacts, and local WordPress browser integration coverage.
* Fixed Pro AI prompt-builder item paging so large category selections do not skip products when loading more.
* Added a manual release smoke checklist for Freemius, email delivery, WordPress.org, and live-site validation.

= 1.1.17 =
* Improved Pro analytics digest scheduling, chart exports, dashboard links, and admin table layout.
* Improved Pro range diagnostics orientation and local index upgrade self-healing.

= 1.1.16 =
* Reorganized the admin settings screen into sticky sections for setup, search, chips, index, diagnostics, Pro rules, range chips, analytics, AI workflows, and advanced maintenance.

= 1.1.15 =
* Fixed duplicate Pro numeric range chips when chip rules and range prioritization both handle the same range.

= 1.1.14 =
* Hydrates bare guaranteed-search shortcode text into the live search UI when a page builder or theme prints the shortcode literally.

= 1.1.13 =
* Improved the Guaranteed Search Mode setup card so shortcode and block guidance stays contained at medium admin widths.

= 1.1.12 =
* Fixed Pro numeric range parsing for hyphenated shared-unit title ranges.

= 1.1.11 =
* Fixed Pro numeric range detection for shared trailing-unit title values.

= 1.1.10 =
* Improved Pro numeric range detection for title-only product dimensions and wattage ranges such as 40 / 60 mm, 172x150 mm, and 200-350 W.

= 1.1.9 =
* Added a legacy shortcode alias so older demo/theme content renders the guaranteed search UI instead of shortcode text.

= 1.1.8 =
* Improved Pro numeric range detection for valve-style values such as DN, PN, and bar, and kept accepted range chips visible when normal chips fill the chip limit.

= 1.1.7 =
* Clarified Guaranteed Search Mode setup guidance so the block path and shortcode fallback are shown as separate choices.

= 1.1.6 =
* Refined Pro numeric range slider handles so desktop controls are smaller and aligned with the track while preserving larger touch targets on coarse pointers.

= 1.1.5 =
* Improved the Pro Agent Prompt selected-items panel so it stays visible and reachable at narrower admin widths.

= 1.1.4 =
* Improved the Pro AI Prompt Builder settings layout so REST write-back descriptions wrap cleanly at narrower widths.

= 1.1.3 =
* Changed Freemius release automation to publish Pro builds immediately instead of leaving them pending.

= 1.1.2 =
* Fixed Freemius release automation to accept the developer ID secret alias and fail before publishing if required deployment secrets are missing.
* Keeps Pro uploads pending for dashboard review.

= 1.1.1 =
* Fixed the automated release package build so the Pro ZIP includes the Freemius SDK and uses a Freemius-compatible archive.
* Tightened release automation so deployments only run from version tags that match plugin metadata.

= 1.1.0 =
* Added CI coverage for unit tests, WP.org packaging validation, linting, and browser compatibility checks.
* Added the Search Debug admin tool with search-path reporting, warnings, generated chips, and sample results.
* Added reusable "why no chips?" explanations for empty results, redundant tags, missing tags, index readiness, WooCommerce settings, and theme selector issues.
* Added local search index controls for rebuilding, processing one batch, clearing, pausing, and resuming indexing.
* Added the Smart Search Chips Gutenberg search block for the guaranteed search UI.
* Improved Pro analytics digest previews, next scheduled run display, and local-reporting privacy messaging.
* Improved Pro range diagnostics with confidence scores, accepted/rejected groups, source labels, and human-readable rejection reasons.
* Added Pro chip rule JSON import/export with validation, preview mode, and admin-only security checks.

= 1.0.15 =
* Added first-run setup guidance with a guaranteed shortcode/block integration path.
* Split shortcode search from optional theme AJAX enhancement so the shortcode always uses the built-in endpoint.
* Made theme AJAX compatibility settings available without Pro gating.
* Added configurable search input selector, keyword field, result container, and result image handling to the compatibility settings.
* Added optional Pro analytics email digests with local-only reporting and CSV attachment support.
* Hardened internal feature checks to avoid direct-install fatals when optional billing components are unavailable.

= 1.0.14 =
* Added compliant admin-only Pro feature links on the plugin row, settings sidebar, and setup panel.
* Clarified contextual Pro messaging without fake locked controls.

= 1.0.13 =
* Restored chip generation to use the currently filtered result set after chip selection.
* Removed the forced AJAX result thumbnail display size by default and added an optional pixel override.

= 1.0.12 =
* Fixed theme search close buttons leaving an empty active results strip.
* Keeps chip suggestions available from the base search set after a chip is selected.
* Increased the default AJAX result image display size.

= 1.0.11 =
* Removed the clear button from Pro numeric range chips.
* Matched numeric range value text styling to normal chip text.

= 1.0.10 =
* Simplified Pro numeric range chips so the unit appears only next to the min/max values.
* Aligned range value and unit typography.

= 1.0.9 =
* Pro numeric range sliders now use labels from the catalog unit/text itself instead of inferred English concepts.
* Groups simple unit variants such as W, V AC/V DC, watt, watts, wattu, and wattů into stable range chips.

= 1.0.8 =
* Improved Pro numeric range parsing for electrical values such as 24 V DC and 230 V AC.
* Keeps unitless model/title numbers out of sliders while still allowing real unit-bearing measurements.

= 1.0.7 =
* Made Pro numeric range chips more conservative so random product title/model numbers do not become sliders.
* Compact range chip styling and removed the Apply button; range changes now auto-apply.

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

== Privacy Policy ==

Smart Search Chips uses [Freemius](https://freemius.com/) for license management and optional usage tracking. When you opt in during activation, basic diagnostic data (WordPress version, PHP version, site URL) is shared with Freemius. You can opt out at any time. The Pro analytics feature stores search data locally in your WordPress database. Optional analytics email digests are sent only by your WordPress site to recipients configured by the administrator; the plugin does not send analytics reports to Smart Search Chips or any third party. Full policy: https://smartsearchchips.app/privacy-policy/

== Upgrade Notice ==

= 1.1.30 =
Adds a Czech archive filter interface option for storefronts whose WordPress site language remains English.

= 1.1.29 =
Moves all selected chips to the far left and keeps all unselected controls after them.

= 1.1.28 =
Makes active category chips and numeric ranges filter custom-theme product lists instead of only the hidden main archive query.

= 1.1.27 =
Keeps Pro numeric range slider handles compact and circular when themes apply global range input styles.

= 1.1.26 =
Adds full-category product archive filtering and reliable top-of-list chip placement for custom themes.

= 1.1.25 =
Adds explicit Pro numeric range linking for paired catalog values using the `=>` marker.
= 1.1.24 =
Keeps selected chips pinned immediately after numeric range chips in frontend search results.

= 1.1.23 =
Improves marketplace positioning and hardens deactivation/uninstall cleanup for scheduled plugin work and optional data deletion.

= 1.1.22 =
Improves the Pro analytics overview and removes public per-result chip count badges from search result cards.

= 1.1.21 =
Restores the Freemius Contact menu while keeping public support links on support@smartsearchchips.app.

= 1.1.20 =
Uses the public support email everywhere and removes the Freemius contact submenu leak path.

= 1.1.19 =
Moves Search Chips into a top-level admin menu and improves frontend search count polish.

= 1.1.18 =
Strengthens release validation and fixes Pro AI selected-item paging for large taxonomy terms.

= 1.1.17 =
Improves Pro analytics digest scheduling, chart exports, dashboard navigation, and several admin diagnostics polish fixes.

= 1.1.16 =
Makes the settings page easier to navigate by grouping setup, diagnostics, local index controls, and Pro workflows into a sticky section nav.

= 1.1.15 =
Fixes duplicate Pro numeric range sliders for the same detected measurement range.

= 1.1.14 =
Restores the live guaranteed-search UI when a theme or builder outputs the search shortcode as plain text.

= 1.1.13 =
Improves the Guaranteed Search Mode setup card layout at medium admin widths.

= 1.1.12 =
Fixes Pro numeric range parsing for hyphenated shared-unit title ranges.

= 1.1.11 =
Fixes Pro numeric range detection for shared trailing-unit title values.

= 1.1.10 =
Improves Pro numeric range detection for title-only product dimensions and wattage ranges.

= 1.1.9 =
Restores older legacy shortcode embeds by routing them to the guaranteed Smart Search Chips search UI.

= 1.1.8 =
Improves Pro numeric range chips for valve-style product searches.

= 1.1.7 =
Clarifies Guaranteed Search Mode setup guidance.

= 1.1.6 =
Refines Pro numeric range slider controls for desktop alignment.

= 1.1.5 =
Keeps the Pro Agent Prompt selected-items panel visible at narrower admin widths.

= 1.1.4 =
Improves Pro AI Prompt Builder settings layout at narrower admin widths.

= 1.1.3 =
Releases Pro builds immediately in Freemius during automated deployment.

= 1.1.2 =
Fixes Freemius release automation and deployment secret validation.

= 1.1.1 =
Fixes automated release packaging for Freemius and keeps deployments tied to matching version tags.

= 1.1.0 =
Adds release-ready diagnostics, local index controls, the guaranteed search block, WP.org package validation, and Pro workflow polish.

= 1.0.15 =
Adds a guaranteed shortcode setup path and improves theme live-search compatibility settings.

= 1.0.14 =
Adds factual admin-only Pro feature links and setup guidance.

= 1.0.13 =
Restores filtered-set chip generation and makes AJAX result thumbnail display sizing opt-in.

= 1.0.12 =
Improves live-search close behavior, chip continuity after selection, and default result image size.

= 1.0.11 =
Removes the range chip clear button and aligns range text styling with normal chips.

= 1.0.10 =
Simplifies numeric range chip labels and typography.

= 1.0.9 =
Range chips now display unit/common text from your catalog instead of inferred labels.

= 1.0.8 =
Restores useful unit-bearing sliders such as V AC/V DC while avoiding unitless junk sliders.

= 1.0.7 =
Improves Pro numeric slider relevance and switches sliders to auto-apply.

= 1.0.6 =
Keeps live search panels open when visitors click chips in theme autocomplete integrations.

= 1.0.5 =
Improves WooCommerce chip generation for existing product catalogs.

= 1.0.4 =
Fixes post type settings saving for product catalogs.

= 1.0.0 =
Initial release.
