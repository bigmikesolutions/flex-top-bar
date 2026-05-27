#!/usr/bin/env bash
set -euo pipefail

COMPOSE_FILE="staging/docker-compose.yml"
PLUGIN_SLUG="flex-top-bar"
PLUGIN_PATH="wp-content/plugins/${PLUGIN_SLUG}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

echo "Starting staging WordPress and database..."
docker compose -f "$COMPOSE_FILE" up -d db wordpress

echo "Waiting for wp-config.php..."
for _ in $(seq 1 60); do
  if docker compose -f "$COMPOSE_FILE" exec -T wordpress test -f /var/www/html/wp-config.php 2>/dev/null; then
    break
  fi
  sleep 2
done

if ! docker compose -f "$COMPOSE_FILE" exec -T wordpress test -f /var/www/html/wp-config.php 2>/dev/null; then
  echo "Timed out waiting for wp-config.php. Check: docker compose -f $COMPOSE_FILE logs wordpress" >&2
  exit 1
fi

echo "Running WordPress install (if needed) and Plugin Check setup..."
docker compose -f "$COMPOSE_FILE" run --rm wp-install

if ! docker compose -f "$COMPOSE_FILE" exec -T wordpress test -d "/var/www/html/${PLUGIN_PATH}" 2>/dev/null; then
  echo "Plugin directory not found at ${PLUGIN_PATH}." >&2
  echo "Ensure plugins/${PLUGIN_SLUG} exists or install the release zip in staging first." >&2
  exit 1
fi

echo "Running Plugin Check for ${PLUGIN_SLUG}..."
docker compose -f "$COMPOSE_FILE" run --rm wp-cli plugin check "${PLUGIN_PATH}" --allow-root \
  --exclude-directories=freemius > wordpress_check.log

echo "Done. Results: wordpress_check.log"
