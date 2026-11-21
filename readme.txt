=== Smart Search Chips ===
Contributors: honzdev
Tags: woocommerce, product search, search filter, ajax search, filter
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Open-source search filter chips for WooCommerce and WordPress: smart chips, numeric range chips, local search analytics, and AI tagging prompts.

== Description ==

Smart Search Chips adds dynamic filter chips to WordPress and WooCommerce search, so visitors can narrow search results by categories, tags, product attributes, and search context instead of getting a flat list of results.

It is built for WooCommerce stores, product catalogs, documentation sites, directories, listing sites, and content-heavy WordPress sites where normal search is not enough. Use the included Search block or `[sschp_search]` shortcode for a reliable search UI, or enhance compatible theme AJAX searches when you want to keep an existing layout.

Example: a visitor searches "lamp" and then narrows results with chips such as LED, outdoor, 12V, brand, category, or wattage range.

Unlike traditional faceted search plugins that require every filter to be designed up front, Smart Search Chips generates useful chips from the current matching results. It hides redundant chips, supports a local search index, and includes diagnostics that show which search path was used and why chips did or did not appear.

Smart Search Chips is free and open source. Since version 1.2.0 every feature is included for everyone, including the ones that used to be sold separately. The source code and issue tracker are on GitHub: https://github.com/JanBartos6/smart-search-chips

**How it works:**

1. A visitor searches through the Smart Search Chips block, shortcode, or a compatible enhanced theme search
2. The plugin finds results through the local index, Relevanssi, or WordPress fallback search
3. It analyzes the matching results, Search Chips Tags, optional WooCommerce helper tags, and mapped ACF/custom fields when configured
4. It displays clickable chips ranked by frequency and usefulness
5. Redundant chips are hidden when they would not narrow the result set
6. Clicking a chip filters the visible results without asking the visitor to rewrite the query

**Search and chips:**

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
* Fully customizable colors, limits, and thresholds
* Safely enhance existing theme AJAX search handlers when compatible
* Theme template overrides for custom result rendering

**Catalog tools:**

* Use existing ACF/custom fields as chip sources instead of manually tagging every item
* Add numeric range chips for values like 100 W, 200 W, dimensions, or mapped numeric fields
* Link paired numeric values with the inline ACF/text marker `=>`, for example 690 V => 63 A
* Normalize equivalent catalog terms with synonym rules
* Control chip labels, pinned chips, hidden chips, and display rules
* Optional local search analytics: zero-result searches, chip clicks, result clicks, impressions, and search-to-result-click rates, stored in your own database
* Send scheduled local analytics email digests to configured site recipients
* AI Prompt Builder that prepares tagging instructions for an external AI tool of your choice (the plugin itself never sends content to an AI provider)
* Import and export chip rules as JSON

**Works best with:**

* WooCommerce stores with many similar products
* Product catalogs using attributes such as size, color, wattage, voltage, brand, model, material, or use case
* Technical catalogs and parts/model-number sites
* Documentation sites and knowledge bases
* Directories, listing sites, resource libraries, and internal portals
* Agencies building search-heavy WordPress sites for clients

== Installation ==

1. Upload the `smart-search-chips` folder to `/wp-content/plugins/`, or install it from Plugins > Add New
2. Activate the plugin through the Plugins menu
3. Go to Search Chips in the WordPress admin sidebar to configure
4. Add Search Chips Tags to your posts/products, or map existing ACF/custom fields
5. Select which post types to search
6. Optionally install Relevanssi (free) for better search ranking

**Quick Start:**

1. Add semicolon-separated tags to the built-in Search Chips Tags field on your posts/products
2. Select which post types to search in Search Chips
3. For the most reliable setup, add the Smart Search Chips Search block, or add `[sschp_search]` to a Shortcode block
4. Optional: use "Override AJAX Action" only when you want to enhance a compatible existing theme search
5. Use Search Chips > Start Here to run a Test Search and confirm the path, chips, and warnings

== Frequently Asked Questions ==

= Is it really free? What happened to the paid version? =

Yes. The separately sold edition was discontinued in version 1.2.0, and all of its features are now part of this plugin under the GPL. There are no licenses, upgrade prompts, or locked features. Development continues in the open on GitHub.

= Is this a hosted SaaS search service? =

No. Smart Search Chips runs as a WordPress plugin on your own site. It does not require an external search service, hosted account, or subscription, and visitor search queries are not sent to a third-party service.

= Do I need Relevanssi? =

No. Smart Search Chips works on its own using its search block/shortcode, local search index, and fallback relevance scoring. Relevanssi is optional and recommended for sites that want stronger WordPress search relevance, especially content-heavy sites.

= How do I populate the tag fields? =

Use the built-in Search Chips Tags meta box on each post/product (tags are semicolon-separated), or map existing ACF or regular custom fields. On WooCommerce stores, helper tags can also come from product attributes, categories, and product tags. For larger catalogs, the AI Prompt Builder prepares tagging instructions you can paste into the AI tool you already use.

= Can I override the search result HTML? =

Yes. Create a file at `your-theme/smart-search-chips/results.php` and the plugin will use it instead of the default template. The variable `$sschp_posts` contains the post data array.

= How does redundancy elimination work? =

The algorithm ranks tags by frequency, then for each candidate chip checks if it appears in ALL results of any already-selected chip's group. If so, it's redundant and hidden. This ensures only truly differentiating tags are shown.

= Can I use this with WooCommerce? =

Yes. Set "Post Types" to include "product". You can use the built-in Search Chips Tags field, WooCommerce helper tags from attributes, categories, and product tags, and mapped ACF or custom fields. SKU chips are optional and disabled by default.

= Does analytics track my visitors? =

Only if you turn it on. Analytics is off by default and asks you to confirm that your site has the required consent or legal basis first. Events are stored in your own WordPress database; nothing is sent to a third party.

= Where do I report bugs or suggest features? =

Use the WordPress.org support forum or open an issue on GitHub: https://github.com/JanBartos6/smart-search-chips/issues

== Screenshots ==

1. WooCommerce-style product search for "backpack" with dynamic filter chips, selected refinements, result counts, prices, and product cards.
2. Search analytics overview with local reporting, export actions, filters, KPI cards, and search funnel metrics.
3. Search analytics details with recommendation cards, zero-result searches, chip CTR, result-click tracking, and query/chip tables.

== Changelog ==

= 1.2.0 =
* Smart Search Chips is now fully open source. Every former Pro feature is included for everyone: ACF/custom field mapping, numeric range chips, synonym and chip rules, local search analytics with email digests, and the AI prompt builder.
* Removed licensing checks and all upgrade prompts.
* Plugin, settings, and feedback links now point to the GitHub repository and the WordPress.org support forum.

= 1.1.32 =
* Removed redundant unit-only Pro numeric ranges when usable named ranges for the same unit exist, while preserving generic ranges for title-only catalogs.

= 1.1.31 =
* Added editable minimum and maximum number fields to Pro numeric range chips, synchronized with the sliders and applied when visitors commit the value.

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

For earlier versions, see CHANGELOG.md in the GitHub repository.

== Privacy Policy ==

Smart Search Chips runs entirely on your WordPress site and does not contact external services.

* Search queries are processed on your site to generate filter chips. They are not stored unless you enable analytics, and they are never sent to a third party.
* The optional local search index stores searchable copies of selected post titles, excerpts, taxonomy terms, configured meta fields, generated chip tags, and numeric facts in your WordPress database. Clearing plugin data or uninstalling with data deletion enabled removes it.
* Analytics is off by default. If you enable it and confirm you have the required consent or legal basis, the plugin stores search and chip interaction events locally: search queries, chip labels, result counts, IDs of clicked posts or products, product category slugs, sanitized same-site page paths, and a server-hashed visitor identifier used for unique-visitor counts.
* Optional analytics email digests are generated by your site and sent only to the recipients you configure. CSV exports omit visitor identifiers and page URLs by default.
* The AI Prompt Builder only prepares text for you to copy into an external AI tool. The plugin does not send content or credentials to an AI provider and does not ask for AI API keys.

== Upgrade Notice ==

= 1.2.0 =
Every former Pro feature is now free and open source. Your existing settings and tags are kept.

= 1.1.32 =
Keeps Pro numeric range chips focused on meaningful named dimensions instead of also showing a redundant unit-only range.

= 1.1.31 =
Adds direct numeric entry alongside Pro range sliders.

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
