#!/usr/bin/env bash
# Build a WordPress-ready zip of the top-bar plugin (for WP.org / Freemius).
# Run from repo: npm run package:zip -w plugins/top-bar
# Optional: ./scripts/package-zip.sh 1.0.6   (override version read from top-bar.php, for logging only)

set -euo pipefail

TOP_BAR_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PLUGINS_DIR="$(cd "$TOP_BAR_DIR/.." && pwd)"
REPO_ROOT="$(cd "$TOP_BAR_DIR/../.." && pwd)"
RELEASE_DIR="$REPO_ROOT/release"
mkdir -p "$RELEASE_DIR"

VERSION="${1:-}"
if [[ -z "$VERSION" ]]; then
  VERSION="$(
    grep -m1 'Version:' "$TOP_BAR_DIR/top-bar.php" \
      | sed -E 's/.*Version:[[:space:]]+([0-9.]+).*/\1/' \
      | tr -d '\r'
  )"
fi

if [[ -z "$VERSION" ]]; then
  echo "Could not read Version from top-bar.php; pass version as first arg." >&2
  exit 1
fi

# Fixed distribution name (version is only in plugin header / constant inside the zip).
OUT="$RELEASE_DIR/flex-top-bar.zip"
cd "$PLUGINS_DIR"
rm -f "$OUT"

zip -r "$OUT" top-bar \
  -x 'top-bar/node_modules/*' \
  -x 'top-bar/node_modules/**' \
  -x 'top-bar/tests/*' \
  -x 'top-bar/tests/**' \
  -x 'top-bar/src/*' \
  -x 'top-bar/src/**' \
  -x 'top-bar/scripts/*' \
  -x 'top-bar/scripts/**' \
  -x 'top-bar/release/*' \
  -x 'top-bar/release/**' \
  -x 'top-bar/package.json' \
  -x 'top-bar/package-lock.json' \
  -x 'top-bar/vite.config.ts' \
  -x 'top-bar/vitest.config.ts' \
  -x 'top-bar/tsconfig.json' \
  -x 'top-bar/tsconfig.node.json' \
  -x 'top-bar/Makefile' \
  -x 'top-bar/.git/*' \
  -x 'top-bar/.git/**'

echo "Packaged v${VERSION} -> $OUT"
