#!/usr/bin/env bash
#
# adniro deploy: run on the server from the project folder, after the new code is in place
# (git pull, or files uploaded). Safe to run again.
#
#   bash deploy/deploy.sh
#
# Config is NOT cached on purpose: LaraClassifier's service providers read .env with env() at
# runtime (cache, queue, social login), and those values are lost once config is cached.
set -euo pipefail

cd "$(dirname "$0")/.."

if ! grep -q '^APP_ENV=production' .env; then
	echo "Warning: APP_ENV is not 'production' in .env" >&2
fi
if grep -q '^APP_DEBUG=true' .env; then
	echo "Warning: APP_DEBUG=true in .env; set it to false on a live site" >&2
fi

php artisan down --retry=60 || true
trap 'php artisan up' EXIT

composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force

php artisan optimize:clear
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link 2>/dev/null || true

# Workers pick up the new code after their current job
php artisan queue:restart

# The live chat's socket server loads the code once, at start. This asks it to stop; systemd
# (deploy/reverb/adniro-reverb.service) starts it again with the new code. Open chats reconnect by themselves.
if grep -q '^BROADCAST_DRIVER=reverb' .env; then
	php artisan reverb:restart
fi

echo "Deployed. Check: curl -s https://$(grep -E '^APP_URL=' .env | sed -E 's#^APP_URL=https?://##')/health"
