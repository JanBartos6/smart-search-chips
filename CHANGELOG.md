# Changelog

The WordPress.org readme lists recent versions; this file keeps the full history.

## 1.2.0

* Smart Search Chips is now fully open source. Every former Pro feature is included for everyone: ACF/custom field mapping, numeric range chips, synonym and chip rules, local search analytics with email digests, and the AI prompt builder.
* Removed the Freemius SDK, licensing checks, and all upgrade prompts.
* Plugin, settings, and feedback links now point to the GitHub repository and the WordPress.org support forum.

## 1.1.32
* Removed redundant unit-only Pro numeric ranges when usable named ranges for the same unit exist, while preserving generic ranges for title-only catalogs.

## 1.1.31
* Added editable minimum and maximum number fields to Pro numeric range chips, synchronized with the sliders and applied when visitors commit the value.

## 1.1.30
* Added an archive filter language setting with Auto, English, and Czech options for headings, descriptions, clear-filter links, and accessible chip actions.
* Added bundled Czech translations and reliable translation loading for both Free and Pro plugin directories.

## 1.1.29
* Fixed chip ordering so every active chip, including selected numeric ranges, appears before every inactive chip.

## 1.1.28
* Fixed product category filters in custom themes that render their visible product cards through a separate secondary query.

## 1.1.27
* Fixed Pro numeric range controls being enlarged or restyled by theme-wide range input CSS, including oversized square slider handles.

## 1.1.26
* Added full-category chip generation and filtering on WooCommerce and custom product category archives, including products beyond the current pagination page.
* Added a custom-theme placement fallback so archive chips appear once, directly above the product list, when standard WordPress or WooCommerce loop hooks are unavailable.

## 1.1.25
* Added Pro numeric range linking with the explicit inline `=>` marker so paired values like `690 V => 63 A` create current ranges scoped to the voltage value.
* Kept plain language values like `690 V pri 63 A` unlinked so existing catalog text does not change behavior unexpectedly.

## 1.1.24
* Fixed chip ordering so numeric range chips stay first and selected chips appear immediately after them before ordinary suggestions.

## 1.1.23
* Reworked the WordPress.org listing copy around WooCommerce/product-search buyer intent, local-plugin positioning, and clearer Pro use cases.
* Added standard deactivation and uninstall cleanup hooks so scheduled plugin work is stopped and opt-in data deletion works through the normal WordPress uninstall flow.

## 1.1.22
* Redesigned the Pro analytics dashboard overview with clearer scorecards, funnel metrics, and data-driven recommendation cards.
* Removed visitor-facing per-result chip count badges from public search result cards while keeping summary and filter-chip counts.

## 1.1.21
* Restored the Freemius Contact menu for licensed customer support.
* Kept public website and admin feedback links on support@smartsearchchips.app.

## 1.1.20
* Replaced public support and feedback mailto links with support@smartsearchchips.app.
* Disabled the Freemius Contact menu entry to avoid exposing account-level contact metadata.
* Updated launch-site legal/support pages to use the public support address.

## 1.1.19
* Moved Smart Search Chips into a top-level WordPress admin sidebar menu with redirects from the old Settings URL.
* Moved Pro analytics and range diagnostics under the Search Chips admin menu for easier navigation.
* Improved frontend chip/result count badges and animated count updates in the guaranteed search UI.

## 1.1.18
* Strengthened release CI with PHP linting, full release builds, package artifacts, and local WordPress browser integration coverage.
* Fixed Pro AI prompt-builder item paging so large category selections do not skip products when loading more.
* Added a manual release smoke checklist for Freemius, email delivery, WordPress.org, and live-site validation.

## 1.1.17
* Improved Pro analytics digest scheduling, chart exports, dashboard links, and admin table layout.
* Improved Pro range diagnostics orientation and local index upgrade self-healing.

## 1.1.16
* Reorganized the admin settings screen into sticky sections for setup, search, chips, index, diagnostics, Pro rules, range chips, analytics, AI workflows, and advanced maintenance.

## 1.1.15
* Fixed duplicate Pro numeric range chips when chip rules and range prioritization both handle the same range.

## 1.1.14
* Hydrates bare guaranteed-search shortcode text into the live search UI when a page builder or theme prints the shortcode literally.

## 1.1.13
* Improved the Guaranteed Search Mode setup card so shortcode and block guidance stays contained at medium admin widths.

## 1.1.12
* Fixed Pro numeric range parsing for hyphenated shared-unit title ranges.

## 1.1.11
* Fixed Pro numeric range detection for shared trailing-unit title values.

## 1.1.10
* Improved Pro numeric range detection for title-only product dimensions and wattage ranges such as 40 / 60 mm, 172x150 mm, and 200-350 W.

## 1.1.9
* Added a legacy shortcode alias so older demo/theme content renders the guaranteed search UI instead of shortcode text.

## 1.1.8
* Improved Pro numeric range detection for valve-style values such as DN, PN, and bar, and kept accepted range chips visible when normal chips fill the chip limit.

## 1.1.7
* Clarified Guaranteed Search Mode setup guidance so the block path and shortcode fallback are shown as separate choices.

## 1.1.6
* Refined Pro numeric range slider handles so desktop controls are smaller and aligned with the track while preserving larger touch targets on coarse pointers.

## 1.1.5
* Improved the Pro Agent Prompt selected-items panel so it stays visible and reachable at narrower admin widths.

## 1.1.4
* Improved the Pro AI Prompt Builder settings layout so REST write-back descriptions wrap cleanly at narrower widths.

## 1.1.3
* Changed Freemius release automation to publish Pro builds immediately instead of leaving them pending.

## 1.1.2
* Fixed Freemius release automation to accept the developer ID secret alias and fail before publishing if required deployment secrets are missing.
* Keeps Pro uploads pending for dashboard review.

## 1.1.1
* Fixed the automated release package build so the Pro ZIP includes the Freemius SDK and uses a Freemius-compatible archive.
* Tightened release automation so deployments only run from version tags that match plugin metadata.

## 1.1.0
* Added CI coverage for unit tests, WP.org packaging validation, linting, and browser compatibility checks.
* Added the Search Debug admin tool with search-path reporting, warnings, generated chips, and sample results.
* Added reusable "why no chips?" explanations for empty results, redundant tags, missing tags, index readiness, WooCommerce settings, and theme selector issues.
* Added local search index controls for rebuilding, processing one batch, clearing, pausing, and resuming indexing.
* Added the Smart Search Chips Gutenberg search block for the guaranteed search UI.
* Improved Pro analytics digest previews, next scheduled run display, and local-reporting privacy messaging.
* Improved Pro range diagnostics with confidence scores, accepted/rejected groups, source labels, and human-readable rejection reasons.
* Added Pro chip rule JSON import/export with validation, preview mode, and admin-only security checks.

## 1.0.15
* Added first-run setup guidance with a guaranteed shortcode/block integration path.
* Split shortcode search from optional theme AJAX enhancement so the shortcode always uses the built-in endpoint.
* Made theme AJAX compatibility settings available without Pro gating.
* Added configurable search input selector, keyword field, result container, and result image handling to the compatibility settings.
* Added optional Pro analytics email digests with local-only reporting and CSV attachment support.
* Hardened internal feature checks to avoid direct-install fatals when optional billing components are unavailable.

## 1.0.14
* Added compliant admin-only Pro feature links on the plugin row, settings sidebar, and setup panel.
* Clarified contextual Pro messaging without fake locked controls.

## 1.0.13
* Restored chip generation to use the currently filtered result set after chip selection.
* Removed the forced AJAX result thumbnail display size by default and added an optional pixel override.

## 1.0.12
* Fixed theme search close buttons leaving an empty active results strip.
* Keeps chip suggestions available from the base search set after a chip is selected.
* Increased the default AJAX result image display size.

## 1.0.11
* Removed the clear button from Pro numeric range chips.
* Matched numeric range value text styling to normal chip text.

## 1.0.10
* Simplified Pro numeric range chips so the unit appears only next to the min/max values.
* Aligned range value and unit typography.

## 1.0.9
* Pro numeric range sliders now use labels from the catalog unit/text itself instead of inferred English concepts.
* Groups simple unit variants such as W, V AC/V DC, watt, watts, wattu, and wattů into stable range chips.

## 1.0.8
* Improved Pro numeric range parsing for electrical values such as 24 V DC and 230 V AC.
* Keeps unitless model/title numbers out of sliders while still allowing real unit-bearing measurements.

## 1.0.7
* Made Pro numeric range chips more conservative so random product title/model numbers do not become sliders.
* Compact range chip styling and removed the Apply button; range changes now auto-apply.

## 1.0.6
* Improved chip-click behavior inside third-party AJAX search panels so selecting a chip keeps the search UI open.
* Improved frontend input discovery for theme search forms that use keyword, s, or query fields.

## 1.0.5
* Added runtime WooCommerce tag fallback so chips can appear from product attributes, categories, and product tags before generated helper tags are stored. SKU chips are optional and disabled by default.

## 1.0.4
* Fixed post type settings persistence when selecting products or other custom post types.
* Added an extra save button below the settings style preview.

## 1.0.0
* Initial release
* Core chip engine with redundancy elimination
* Relevanssi integration with relevance threshold
* AJAX search handler with chip filtering
* Admin settings page
* Shortcode [sschp_search]
* Theme template override support
