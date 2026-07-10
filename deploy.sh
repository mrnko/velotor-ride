#!/usr/bin/env bash
#
# ВелоТОР — production deploy script.
#
# Run from the project root on the server (the GitHub Actions workflow does this
# automatically after a push to main). It pulls the latest code, installs
# dependencies, installs the pre-built frontend assets and runs migrations.
#
# Fails fast: if any important command fails the script exits non-zero, but the
# application is always taken back out of maintenance mode via the EXIT trap so
# the site is never left offline.
#
# Usage:
#   ./deploy.sh ".deploy/<commit-sha>/public/build"

set -euo pipefail

cd "$(dirname "$0")"

log() {
    printf '\n\033[1;34m==> %s\033[0m\n' "$1"
}

BUILD_PATH="${1:-}"

if [ -z "$BUILD_PATH" ]; then
    echo "ERROR: Build path is required."
    echo "Usage: ./deploy.sh .deploy/<commit-sha>/public/build"
    exit 1
fi

if [ ! -f "$BUILD_PATH/manifest.json" ]; then
    echo "ERROR: Vite manifest not found at: $BUILD_PATH/manifest.json"
    exit 1
fi

bring_up() {
    php artisan up || true
}

trap bring_up EXIT

log "Enabling maintenance mode"
php artisan down --retry=60 || true

log "Pulling latest code"
git fetch origin main
git reset --hard origin/main

log "Installing PHP dependencies"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

log "Installing prebuilt frontend assets"
rm -rf public/build
mkdir -p public
cp -R "$BUILD_PATH" public/build

VERSION_FILE="$(dirname "$BUILD_PATH")/deploy-version.txt"

log "Installing deploy version file"
if [ -f "$VERSION_FILE" ]; then
    cp "$VERSION_FILE" public/deploy-version.txt
else
    echo "WARNING: deploy-version.txt not found at $VERSION_FILE"
fi

log "Checking frontend manifest"
test -f public/build/manifest.json

log "Running database migrations"
php artisan migrate --force

log "Clearing caches"
php artisan optimize:clear

log "Caching config, routes and views"
php artisan optimize

log "Ensuring storage symlink"
php artisan storage:link || true

log "Disabling maintenance mode"
php artisan up

log "Cleaning old deployment builds"
rm -rf .deploy

log "Deploy complete"
