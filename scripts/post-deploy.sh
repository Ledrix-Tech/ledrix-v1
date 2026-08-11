#!/usr/bin/env bash
# Run after each production deploy (from project root).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "==> Ledrix post-deploy"

php artisan down --retry=60 || true

# Primary (tenant CRM) DB
php artisan migrate --force

# Central (SaaS / Super Admin) DB
php artisan migrate --database=central --path=database/migrations/central --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Meta Pixel / domain verification only work if production .env has:
#   APP_URL=https://ledrix.co
#   FACEBOOK_DOMAIN_VERIFICATION=...
#   META_PIXEL_ID=...
# then re-run config:cache (never cache with localhost APP_URL).

# Ensure queue tables exist when using database driver
php artisan queue:restart 2>/dev/null || true

php artisan up

echo "==> Done. Cron should call: php artisan schedule:run (every minute)"
echo "==> Verify: php artisan schedule:list"
