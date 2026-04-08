#!/usr/bin/env bash
# Build a WordPress-ready zip of the top-bar plugin (for WP.org / Freemius).
# Run from repo: npm run package:zip -w plugins/flex-top-bar
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

zip -r "$OUT" flex-top-bar \
  -x 'flex-top-bar/node_modules/*' \
  -x 'flex-top-bar/node_modules/**' \
  -x 'flex-top-bar/tests/*' \
  -x 'flex-top-bar/tests/**' \
  -x 'flex-top-bar/src/*' \
  -x 'flex-top-bar/src/**' \
  -x 'flex-top-bar/scripts/*' \
  -x 'flex-top-bar/scripts/**' \
  -x 'flex-top-bar/release/*' \
  -x 'flex-top-bar/release/**' \
  -x 'flex-top-bar/package.json' \
  -x 'flex-top-bar/package-lock.json' \
  -x 'flex-top-bar/vite.config.ts' \
  -x 'flex-top-bar/vitest.config.ts' \
  -x 'flex-top-bar/tsconfig.json' \
  -x 'flex-top-bar/tsconfig.node.json' \
  -x 'flex-top-bar/Makefile' \
  -x 'flex-top-bar/.git/*' \
  -x 'flex-top-bar/.git/**'

echo "Packaged v${VERSION} -> $OUT"
