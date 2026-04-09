#!/usr/bin/env bash
# Build a WordPress-ready zip of the top-bar plugin (for WP.org / Freemius).
# Run from repo: npm run package:zip -w plugins/flex-top-bar
# Optional: ./scripts/package-zip.sh 1.0.6   (override version read from flex-top-bar.php, for logging only)

set -euo pipefail

TOP_BAR_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PLUGINS_DIR="$(cd "$TOP_BAR_DIR/.." && pwd)"
REPO_ROOT="$(cd "$TOP_BAR_DIR/../.." && pwd)"
RELEASE_DIR="$REPO_ROOT/release"
mkdir -p "$RELEASE_DIR"

VERSION="${1:-}"
if [[ -z "$VERSION" ]]; then
  VERSION="$(
    grep -m1 'Version:' "$TOP_BAR_DIR/flex-top-bar.php" \
      | sed -E 's/.*Version:[[:space:]]+([0-9.]+).*/\1/' \
      | tr -d '\r'
  )"
fi

if [[ -z "$VERSION" ]]; then
  echo "Could not read Version from flex-top-bar.php; pass version as first arg." >&2
  exit 1
fi

# Fixed distribution name (version is only in plugin header / constant inside the zip).
OUT="$RELEASE_DIR/flex-top-bar.zip"
cd "$PLUGINS_DIR"
rm -f "$OUT"
rm -f "$RELEASE_DIR/flex-top-bar-dist.zip"

# Stage only allowlisted files/dirs, then zip the staged plugin.
TMP_DIR="$(mktemp -d "${RELEASE_DIR%/}/flex-top-bar-zip.XXXXXX")"
trap 'rm -rf "$TMP_DIR"' EXIT

STAGED_PLUGIN_DIR="$TMP_DIR/flex-top-bar"
mkdir -p "$STAGED_PLUGIN_DIR/assets"

cp "$TOP_BAR_DIR/flex-top-bar.php" "$STAGED_PLUGIN_DIR/"
cp -R "$TOP_BAR_DIR/includes" "$STAGED_PLUGIN_DIR/"
cp -R "$TOP_BAR_DIR/freemius" "$STAGED_PLUGIN_DIR/"
cp -R "$TOP_BAR_DIR/assets/doc" "$STAGED_PLUGIN_DIR/assets/"

mkdir -p "$STAGED_PLUGIN_DIR/assets/dist"
cp -R "$TOP_BAR_DIR/assets/dist/"* "$STAGED_PLUGIN_DIR/assets/dist/"

# Also include an unminified build alongside the packaged minified build.
if [[ -d "$TOP_BAR_DIR/assets/dist-dev" ]]; then
  cp -R "$TOP_BAR_DIR/assets/dist-dev" "$STAGED_PLUGIN_DIR/assets/"
fi

# Ensure plugin docs are included (both `doc/` and `docs/`).
if [[ -d "$TOP_BAR_DIR/doc" ]]; then
  cp -R "$TOP_BAR_DIR/doc" "$STAGED_PLUGIN_DIR/"
fi
if [[ -d "$TOP_BAR_DIR/docs" ]]; then
  cp -R "$TOP_BAR_DIR/docs" "$STAGED_PLUGIN_DIR/"
fi

cd "$TMP_DIR"
zip -r "$OUT" flex-top-bar

echo "Packaged v${VERSION} -> $OUT"
