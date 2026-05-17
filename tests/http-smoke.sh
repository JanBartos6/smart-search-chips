#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${SSCHP_TEST_BASE_URL:-http://localhost:8080}"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

if command -v rg >/dev/null 2>&1 && rg --version >/dev/null 2>&1; then
  SSCHP_SEARCH_CMD="rg"
else
  SSCHP_SEARCH_CMD="grep"
fi

has_match() {
  local pattern="$1"
  local file="$2"

  if [[ "$SSCHP_SEARCH_CMD" == "rg" ]]; then
    rg -q --fixed-strings "$pattern" "$file"
  else
    grep -F -q -- "$pattern" "$file"
  fi
}

count_fixed_matches() {
  local pattern="$1"
  local file="$2"

  if [[ "$SSCHP_SEARCH_CMD" == "rg" ]]; then
    rg -o --fixed-strings "$pattern" "$file" | wc -l | tr -d ' '
  else
    grep -F -o -- "$pattern" "$file" | wc -l | tr -d ' '
  fi
}

require_match() {
  local file="$1"
  local pattern="$2"
  local message="$3"

  if ! has_match "$pattern" "$file"; then
    echo "FAIL: $message" >&2
    echo "Missing pattern: $pattern" >&2
    exit 1
  fi
}

count_matches() {
  local file="$1"
  local pattern="$2"
  count_fixed_matches "$pattern" "$file"
}

curl -fsS "$BASE_URL/search/" > "$TMP_DIR/search-page.html"
require_match "$TMP_DIR/search-page.html" 'js-ssc-search' 'Shortcode search page should render the built-in search form.'
require_match "$TMP_DIR/search-page.html" 'ssc-search__results' 'Shortcode search page should render the result container.'

curl -fsS -X POST "$BASE_URL/wp-admin/admin-ajax.php" \
  --data 'action=sschp_search&keyword=Trapped' > "$TMP_DIR/ajax-trapped.html"
require_match "$TMP_DIR/ajax-trapped.html" '<div class="ssc-chips">' 'AJAX search should return chips for the Trapped demo query.'
require_match "$TMP_DIR/ajax-trapped.html" 'spring handle' 'AJAX search should include at least one expected chip label.'
require_match "$TMP_DIR/ajax-trapped.html" 'Trapped Key Interlock with Lockout' 'AJAX search should include expected results.'

curl -fsS "$BASE_URL/?s=Trapped" > "$TMP_DIR/results-unfiltered.html"
require_match "$TMP_DIR/results-unfiltered.html" 'ssc-loop-filters' 'Search results page should render chips above the loop.'
require_match "$TMP_DIR/results-unfiltered.html" 'Refine This Search' 'Search results page should show the archive chip heading.'

curl -fsS "$BASE_URL/?s=Trapped&sschp_tags=spring%20handle" > "$TMP_DIR/results-filtered.html"
require_match "$TMP_DIR/results-filtered.html" 'Clear filters' 'Filtered results page should render a clear-filters link.'
require_match "$TMP_DIR/results-filtered.html" 'ssc-chip ssc-chip--active' 'Filtered results page should mark the chosen chip as active.'

UNFILTERED_COUNT="$(count_matches "$TMP_DIR/results-unfiltered.html" 'href="http://localhost:8080/trapped-key')"
FILTERED_COUNT="$(count_matches "$TMP_DIR/results-filtered.html" 'href="http://localhost:8080/trapped-key')"

if [[ "$FILTERED_COUNT" -ge "$UNFILTERED_COUNT" ]]; then
  echo "FAIL: Applying a chip on the public results page should narrow the visible result set." >&2
  echo "Unfiltered count: $UNFILTERED_COUNT" >&2
  echo "Filtered count:   $FILTERED_COUNT" >&2
  exit 1
fi

echo "All HTTP smoke tests passed."
