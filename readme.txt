=== Smart Search Chips ===
Contributors: honzdev
Tags: search, filter, chips, woocommerce, product search
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.13
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Dynamic filter chips for WordPress search. Auto-generates smart, non-redundant filter tags from your search results.

== Description ==

Smart Search Chips adds dynamic, clickable filter chips to your WordPress search results. Unlike traditional faceted search plugins that require you to predefine all filter options, Smart Search Chips automatically generates relevant filter tags from the actual search results.

**How it works:**

1. User types a search query
2. Plugin analyzes the top results and their custom field tags
3. Displays clickable filter chips ranked by frequency
4. Automatically eliminates redundant chips (tags shared by all results)
5. Clicking a chip filters results to only show matching products

**Key Features:**

* Dynamic chip generation from search results - no predefined facets needed
* Smart redundancy elimination - chips that don't help narrow results are hidden
* Works with Relevanssi (recommended) for relevance-based scoring
* Uses custom fields (ACF or regular post meta) as tag sources
* AJAX-powered live search with instant filtering
* Fully customizable colors, limits, and thresholds
* Override existing theme AJAX search handlers
* Synonym rules for normalizing equivalent catalog terms into one chip
* Numeric range slider chips for values like 100 W, 200 W, or mapped numeric fields
* Manual chip curation with pinned chips, hidden chips, and display label overrides
* Search analytics for query, chip-click, impression, and zero-result insights
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
4. Set your custom field names (primary tag field + extra tag field)
5. Select which post types to search
6. Optionally install Relevanssi (free) for better search ranking

**Quick Start:**

1. Create a custom field on your posts/products (e.g. `search_tags`) with semicolon-separated tags
2. In plugin settings, set "Primary Tag Field" to your field name
3. Add `[sschp_search]` shortcode to any page, or set "Override AJAX Action" to hook into your theme's existing search

== Frequently Asked Questions ==

= Do I need Relevanssi? =

No, but it's recommended. Without Relevanssi, the plugin uses default WordPress search. With Relevanssi, you get relevance-based scoring which makes the chip generation smarter (irrelevant results are filtered out before chips are calculated).

= How do I populate the tag fields? =

You can use ACF (Advanced Custom Fields), regular WordPress custom fields, or populate them programmatically via the REST API. Tags should be semicolon-separated in the primary field and comma-separated in the extra field.

= Can I override the search result HTML? =

Yes. Create a file at `your-theme/smart-search-chips/results.php` and the plugin will use it instead of the default template. The variable `$sschp_posts` contains the post data array.

= How does redundancy elimination work? =

The algorithm ranks tags by frequency, then for each candidate chip checks if it appears in ALL results of any already-selected chip's group. If so, it's redundant and hidden. This ensures only truly differentiating tags are shown.

= Can I use this with WooCommerce? =

Yes. Set "Post Types" to include "product" and use WooCommerce product attributes or ACF fields as your tag source.

= Can external automation manage Pro chip rules? =

Yes. Smart Search Chips Pro exposes authenticated REST access for chip rules at `/wp-json/smart-search-chips/v1/chip-rules`. Administrators can read or update `synonym_rules`, `pinned_chips`, `hidden_chips`, and `renamed_chips` using standard WordPress REST authentication.

== Screenshots ==

1. Search for "tea" and filter chips appear automatically
2. Click a chip to narrow results instantly
3. The Smart Search Chips demo homepage

== Changelog ==

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
* Added runtime WooCommerce tag fallback so chips can appear from product attributes, categories, tags, and SKU before stored chip tags are generated.

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

Smart Search Chips uses [Freemius](https://freemius.com/) for license management and optional usage tracking. When you opt in during activation, basic diagnostic data (WordPress version, PHP version, site URL) is shared with Freemius. You can opt out at any time. The Pro analytics feature stores search data locally in your WordPress database — it is never sent externally. Full policy: https://smartsearchchips.app/privacy-policy/

== Upgrade Notice ==

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
