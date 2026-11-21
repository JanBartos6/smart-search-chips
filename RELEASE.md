# Releasing

Releases are cut from version tags. Pushing a tag such as `v1.2.0` runs `.github/workflows/release.yml`, which:

1. checks that the tag matches the plugin header, `SSCHP_VERSION`, the readme `Stable tag`, and the block asset version;
2. runs the unit tests, builds the WordPress.org package, validates and lints it, and runs the browser compatibility tests;
3. creates the GitHub release with the package ZIP attached;
4. deploys the package to WordPress.org SVN (needs the `WPORG_USERNAME` / `WPORG_PASSWORD` secrets in the `release` environment).

## Checking a release locally

```bash
npm run release:wporg:check   # Windows / PowerShell: build, validate, lint, unit tests
bash build.sh                 # Linux / macOS: build the same package
php scripts/validate-wporg-package.php
php tests/unit.php
```

The generated package is:

```text
build/smart-search-chips-wporg/smart-search-chips
```

If you ever deploy to SVN by hand, copy only the contents of that directory into `trunk` and tag it. Never copy the raw repository (tests, scripts, marketing files) to SVN.

## Version bump checklist

- `smart-search-chips.php`: `Version:` header and `SSCHP_VERSION`
- `readme.txt`: `Stable tag`, changelog entry, upgrade notice
- `blocks/search/editor.asset.php`: `version`
- `CHANGELOG.md`: full changelog entry
