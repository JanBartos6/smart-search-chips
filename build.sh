#!/bin/bash
set -euo pipefail

PLUGIN_SLUG="smart-search-chips"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
BUILD_DIR="$SCRIPT_DIR/build"
DIST_DIR="$BUILD_DIR/$PLUGIN_SLUG"
ZIP_FILE="$BUILD_DIR/$PLUGIN_SLUG.zip"

FREEMIUS_VERSION="2.13.1"

# Clean previous build
rm -rf "$BUILD_DIR"
mkdir -p "$DIST_DIR"

# Download Freemius SDK if not present
if [ ! -f "$SCRIPT_DIR/vendor/freemius/start.php" ]; then
    echo "Downloading Freemius SDK v${FREEMIUS_VERSION}..."
    mkdir -p "$SCRIPT_DIR/vendor/freemius"
    curl -sL "https://github.com/Freemius/wordpress-sdk/archive/refs/tags/${FREEMIUS_VERSION}.tar.gz" \
        -o /tmp/freemius-sdk.tar.gz
    tar -xzf /tmp/freemius-sdk.tar.gz -C "$SCRIPT_DIR/vendor/freemius" --strip-components=1
    rm /tmp/freemius-sdk.tar.gz
    echo "Freemius SDK installed."
fi

# Build exclude pattern for tar from .distignore
EXCLUDES=""
while IFS= read -r line || [ -n "$line" ]; do
    line="$(echo "$line" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
    [ -z "$line" ] && continue
    [[ "$line" == \#* ]] && continue
    EXCLUDES="$EXCLUDES --exclude=$line"
done < "$SCRIPT_DIR/.distignore"

# Copy plugin files using tar (portable — works without rsync)
tar -cf - -C "$SCRIPT_DIR" $EXCLUDES --exclude=build . | tar -xf - -C "$DIST_DIR"

# Create zip (use zip if available, fall back to Python for correct path separators)
cd "$BUILD_DIR"
if command -v zip &>/dev/null; then
    zip -qr "$ZIP_FILE" "$PLUGIN_SLUG/"
else
    python -c "
import zipfile, os
zf = zipfile.ZipFile('$PLUGIN_SLUG.zip', 'w', zipfile.ZIP_DEFLATED)
for root, dirs, files in os.walk('$PLUGIN_SLUG'):
    for f in files:
        fp = os.path.join(root, f)
        arc = fp.replace(os.sep, '/')
        zf.write(fp, arc)
zf.close()
"
fi

echo "Done: $ZIP_FILE"
