#!/bin/bash
# Build the WordPress.org package on Linux/macOS.
# Mirrors scripts/build-release.ps1: build/smart-search-chips-wporg/smart-search-chips + .zip
set -euo pipefail

SLUG="smart-search-chips"
ROOT="$(cd "$(dirname "$0")" && pwd)"
BUILD_ROOT="$ROOT/build"
PARENT="$BUILD_ROOT/$SLUG-wporg"
PKG="$PARENT/$SLUG"
ZIP="$BUILD_ROOT/$SLUG-wporg.zip"

rm -rf "$BUILD_ROOT"
mkdir -p "$PKG"

for file in smart-search-chips.php readme.txt icon.png screenshot-1.png screenshot-2.png screenshot-3.png; do
    [ -f "$ROOT/$file" ] && cp "$ROOT/$file" "$PKG/$file"
done

for dir in assets blocks includes languages; do
    [ -d "$ROOT/$dir" ] && cp -R "$ROOT/$dir" "$PKG/$dir"
done

rm -rf "$PKG/vendor" "$PKG/languages/$SLUG.pot"

(cd "$PARENT" && zip -qr "$ZIP" "$SLUG")
echo "Built WordPress.org package: $ZIP"
