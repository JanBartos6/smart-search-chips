# Release Checks

Before uploading to WordPress.org SVN, run the full generated-package check:

```bash
npm run release:wporg:check
```

This command builds the WordPress.org package, runs the existing WordPress.org validator, validates the generated package shape, lints PHP syntax inside the generated package, and runs the lightweight unit tests.

The generated WordPress.org package path is:

```text
build/smart-search-chips-wporg/smart-search-chips
```

Only copy the contents of that generated package directory into SVN `trunk`. Never copy the raw GitHub repository, the Pro build, `vendor`, Freemius files, or premium-only files to WordPress.org SVN.

WordPress.org-specific source files live in `build-sources/wporg/` and are overlaid during `npm run build:wporg`. If a file must differ between the Pro/dev build and the WordPress.org build, update the explicit file in `build-sources/wporg/` instead of adding regex patching to the release script.

Useful individual checks:

```bash
npm run build:wporg
npm run validate:wporg
npm run lint:wporg
npm run test:unit
```
