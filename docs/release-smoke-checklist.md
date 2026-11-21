# Release Smoke Checklist

Use this checklist for release behaviors that need real email delivery, real catalogs, or theme/plugin combinations that are intentionally not part of the automated CI environment.

## Upgrades

- Confirm an upgrade from 1.1.x (the former WordPress.org build) keeps settings and tags, creates the analytics table, and shows every settings section.
- Confirm a fresh install activates cleanly and queues the initial search index.

## WordPress.org Release

- Confirm the release workflow built and validated the WordPress.org package before deploying.
- Confirm the WordPress.org package activates on a clean WordPress install.
- Confirm the WP.org readme, plugin header, stable tag, and ZIP folder shape match the intended version.

## Real Site And Theme Coverage

- Test the guaranteed `[sschp_search]` shortcode on a classic theme.
- Test the Smart Search Chips Search block on a block theme.
- Test one page-builder-like page where the shortcode/block is embedded in builder content.
- Test the optional theme AJAX enhancement on at least one compatible live theme search.
- Test a real WooCommerce catalog with products, attributes, categories, tags, SKUs, and missing data.

## Catalog Tools

- Send a real analytics test digest to configured recipients.
- Confirm scheduled analytics emails arrive with the expected CSV/chart attachment choices.
- Confirm analytics remains disabled until the admin enables it and acknowledges consent/legal basis.
- Confirm range diagnostics make sense on a real product catalog.
- Confirm AI prompt-builder REST write-back remains off by default.
- Confirm AI prompt-builder selected-item paging works on a large category or taxonomy term.

## Dogfood

- Run at least one real search that should produce normal chips.
- Run at least one real search that should produce range chips.
- Check the settings tabs at desktop, medium, and narrow admin widths.
