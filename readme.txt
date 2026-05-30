=== Smart Search Chips ===
Contributors: honzdev
Tags: search, filter, chips, woocommerce, product search
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.17
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Dynamic filter chips for WordPress search. Auto-generates smart, non-redundant filter tags from your search results.

== Description ==

Smart Search Chips adds dynamic, clickable filter chips to your WordPress search results. Unlike traditional faceted search plugins that require you to predefine all filter options, Smart Search Chips automatically generates relevant filter tags from the actual search results.

**How it works:**

1. User types a search query
2. Plugin analyzes the top results and their Search Chips Tags or Pro custom field tags
3. Displays clickable filter chips ranked by frequency
4. Automatically eliminates redundant chips (tags shared by all results)
5. Clicking a chip filters results to only show matching products

**Key Features:**

* Dynamic chip generation from search results - no predefined facets needed
* Smart redundancy elimination - chips that don't help narrow results are hidden
* Works with Relevanssi (recommended) for relevance-based scoring
* Uses the built-in Search Chips Tags field, with ACF/custom field connectors in Pro
* AJAX-powered live search with instant filtering
* Fully customizable colors, limits, and thresholds
* Guaranteed shortcode search mode that does not depend on theme AJAX
* Safely enhance existing theme AJAX search handlers when compatible
* Synonym rules for normalizing equivalent catalog terms into one chip
* Numeric range slider chips for values like 100 W, 200 W, or mapped numeric fields
* Manual chip curation with pinned chips, hidden chips, and display label overrides
* Search analytics for query, chip-click, impression, and zero-result insights
* Optional local analytics email digests for configured site recipients
* AI prompt builder for external agent-assisted tag workflows
* Theme template overrides for custom result rendering
* Shortcode [sschp_search] for easy integration

**Works best with:**

* Product catalogs with many similar items
* Technical documentation sites
* Any site where search results need smart filtering

== Installation ==

1. Upload the `smart-search-chips` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to Settings > Search Chips to configure
4. Add Search Chips Tags to your posts/products, or map existing custom fields in Pro
5. Select which post types to search
6. Optionally install Relevanssi (free) for better search ranking

**Quick Start:**

1. Add semicolon-separated tags to the built-in Search Chips Tags field on your posts/products
2. Select which post types to search in Settings > Search Chips
3. For the most reliable setup, add `[sschp_search]` to any page or paste it into a WordPress Shortcode block
4. Optional: use "Override AJAX Action" only when you want to enhance a compatible existing theme search

== Frequently Asked Questions ==

= Do I need Relevanssi? =

No, but it's recommended. Without Relevanssi, the plugin uses default WordPress search. With Relevanssi, you get relevance-based scoring which makes the chip generation smarter (irrelevant results are filtered out before chips are calculated).

= How do I populate the tag fields? =

Free uses the built-in Search Chips Tags field. Pro can map existing ACF or regular WordPress custom fields and can expose authenticated REST workflows for chip rules and AI-assisted tagging.

= Can I override the search result HTML? =

Yes. Create a file at `your-theme/smart-search-chips/results.php` and the plugin will use it instead of the default template. The variable `$sschp_posts` contains the post data array.

= How does redundancy elimination work? =

The algorithm ranks tags by frequency, then for each candidate chip checks if it appears in ALL results of any already-selected chip's group. If so, it's redundant and hidden. This ensures only truly differentiating tags are shown.

= Can I use this with WooCommerce? =

Yes. Set "Post Types" to include "product". Free can use the built-in Search Chips Tags field and WooCommerce helper tags from attributes, categories, and product tags. SKU chips are optional and disabled by default; Pro can also map ACF or regular custom fields.

= Can external automation manage Pro chip rules? =

Yes. Smart Search Chips Pro exposes authenticated REST access for chip rules at `/wp-json/smart-search-chips/v1/chip-rules`. Administrators can read or update `synonym_rules`, `pinned_chips`, `hidden_chips`, and `renamed_chips` using standard WordPress REST authentication.

== Screenshots ==

1. Search for "tea" and filter chips appear automatically
2. Click a chip to narrow results instantly
3. The Smart Search Chips demo homepage

== Changelog ==

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
