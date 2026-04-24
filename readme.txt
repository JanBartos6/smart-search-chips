=== Smart Search Chips ===
Contributors: honzdev
Tags: search, filter, chips, woocommerce, product search
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
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

== Screenshots ==

1. Search for "tea" and filter chips appear automatically
2. Click a chip to narrow results instantly
3. The Smart Search Chips demo homepage

== Changelog ==

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

= 1.0.0 =
Initial release.
