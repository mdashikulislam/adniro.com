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

# Artisan runs as the owner of storage/ (the web server's user) when this script is run as root: what it
# writes there (compiled views, the day's log, the cache) then stays writable for the site. Composer and git
# stay with whoever runs the script.
STORAGE_OWNER="$(stat -c '%U' storage 2>/dev/null || stat -f '%Su' storage)"
RUN_AS=""
if [ "$(id -u)" = "0" ] && [ "$STORAGE_OWNER" != "root" ]; then
	RUN_AS="$STORAGE_OWNER"
fi

artisan() {
	if [ -n "$RUN_AS" ]; then
		sudo -u "$RUN_AS" php artisan "$@"
	else
		php artisan "$@"
	fi
}

artisan down --retry=60 || true
trap 'artisan up' EXIT

# With the development packages, on purpose: config/scribe.php (the API documentation) needs one of them
# (knuckleswtf/scribe), and without it no artisan command runs at all. They do nothing on a live site with
# APP_DEBUG=false.
composer install --optimize-autoloader --no-interaction
if [ -n "$RUN_AS" ]; then
	chown -R "$RUN_AS" bootstrap/cache # composer's "package:discover" wrote there as root
fi

artisan migrate --force

# This empties the cache too: /health says "scheduler: fail" until the scheduler's next run, a minute at most
artisan optimize:clear
artisan route:cache
artisan view:cache
artisan event:cache
artisan storage:link 2>/dev/null || true

# Workers pick up the new code after their current job
artisan queue:restart

# The live chat's socket server loads the code once, at start. This asks it to stop; systemd
# (deploy/reverb/adniro-reverb.service) starts it again with the new code. Open chats reconnect by themselves.
if grep -q '^BROADCAST_DRIVER=reverb' .env; then
	artisan reverb:restart
fi

# The site's address, with or without quotes around it in .env
SITE_URL="$(grep -E '^APP_URL=' .env | head -1 | cut -d= -f2- | tr -d '"'"'"'"' | sed -E 's#/+$##')"
echo "Deployed. Check: curl -s ${SITE_URL}/health"
