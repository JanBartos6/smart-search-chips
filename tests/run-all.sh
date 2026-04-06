#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DOCKER_DIR="$(cd "$ROOT_DIR/../ssc-test" 2>/dev/null && pwd || true)"

if command -v php >/dev/null 2>&1; then
  php "$ROOT_DIR/tests/unit.php"
elif [[ -n "${DOCKER_DIR:-}" ]] && command -v docker >/dev/null 2>&1; then
  docker compose -f "$DOCKER_DIR/docker-compose.yml" exec -T wordpress php \
    /var/www/html/wp-content/plugins/smart-search-chips/tests/unit.php
else
  echo "FAIL: No local php binary found and no Docker fallback available for unit tests." >&2
  exit 1
fi

"$ROOT_DIR/tests/http-smoke.sh"
