#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
EXPORT_DIR="$ROOT_DIR/_export"

if [ -d "$EXPORT_DIR" ]; then
  echo "_export directory already exists. Remove it and retry."
  exit 1
fi

mkdir -p "$EXPORT_DIR"

# Export generated Symfony app from volume into _export
( cd "$ROOT_DIR" && docker compose run --rm -v "$EXPORT_DIR:/export" app bash -c "cp -a /var/www/. /export" )

# Remove bootstrap-only files
rm -rf \
  "$ROOT_DIR/docker" \
  "$ROOT_DIR/docker-compose.yml" \
  "$ROOT_DIR/Dockerfile" \
  "$ROOT_DIR/Makefile" \
  "$ROOT_DIR/.dockerignore" \
  "$ROOT_DIR/README.md" \
  "$ROOT_DIR/.gitignore" \
  "$ROOT_DIR/app" \
  "$ROOT_DIR/.github" \
  "$ROOT_DIR/scripts"

# Copy exported app to repo root
cp -a "$EXPORT_DIR/." "$ROOT_DIR/"

# Cleanup
rm -rf "$EXPORT_DIR"

echo "Done. Repository now contains only the Symfony app."
