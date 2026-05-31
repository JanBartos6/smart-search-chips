# Release Smoke Checklist

Use this checklist for release behaviors that need real third-party accounts, real email delivery, or theme/plugin combinations that are intentionally not part of the automated CI environment.

## Freemius And Licensing

- Complete a Freemius checkout for a Pro build.
- Activate a Pro license on a clean WordPress install.
- Confirm the free-to-Pro handoff preserves settings and does not fatal.
- Confirm a Pro-to-free downgrade leaves public search working and hides Pro-only UI safely.
- Confirm the uploaded Freemius build is marked as released, not pending.

## WordPress.org Release

- Upload the generated WordPress.org package to SVN in a dry run or staging flow first when available.
- Confirm the WP.org package activates without premium-only files.
- Confirm the WP.org readme, plugin header, stable tag, and ZIP folder shape match the intended version.

## Real Site And Theme Coverage

- Test the guaranteed `[sschp_search]` shortcode on a classic theme.
- Test the Smart Search Chips Search block on a block theme.
- Test one page-builder-like page where the shortcode/block is embedded in builder content.
- Test the optional theme AJAX enhancement on at least one compatible live theme search.
- Test a real WooCommerce catalog with products, attributes, categories, tags, SKUs, and missing data.

## Pro Workflows

- Send a real analytics test digest to configured recipients.
- Confirm scheduled analytics emails arrive with the expected CSV/chart attachment choices.
- Confirm analytics remains disabled until the admin enables it and acknowledges consent/legal basis.
- Confirm range diagnostics make sense on a real product catalog.
- Confirm AI prompt-builder REST write-back remains off by default.
- Confirm AI prompt-builder selected-item paging works on a large category or taxonomy term.

## Dogfood

- Test the latest released build on `smartsearchchips.app`.
- Run at least one real search that should produce normal chips.
- Run at least one real search that should produce range chips.
- Check the settings tabs at desktop, medium, and narrow admin widths.
