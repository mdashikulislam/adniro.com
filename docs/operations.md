# Running adniro in production

What the server needs, how to deploy, and how to know it is healthy. Files referred to here are in `deploy/`.

## 1. Before the first deploy (.env)

| Setting | Value | Why |
|---|---|---|
| `APP_ENV` | `production` | Production behaviour and error pages. |
| `APP_DEBUG` | `false` | With `true`, error pages show code and server details. |
| `APP_URL` | `https://adniro.com` | Links in emails and the sitemap. |
| `TRUSTED_PROXIES` | your proxy or load balancer address, or empty | `*` lets visitors fake their IP address and bypass per-IP limits. |
| `REDIS_QUEUE_RETRY_AFTER` | `330` or more | Must stay above the AI check time-out (300 s), or a check can run twice. |
| `LOG_DAILY_DAYS` | `14` (default) | Days of logs kept in `storage/logs`. |
| `GUEST_PAGE_CACHE` | `false` until the CDN skips its cache for visitors with a session cookie | Otherwise signed-in people could get a cached guest page. |

## 2. Queue workers (Supervisor)

Emails, thumbnails and AI checks run in the background once the queue is on Redis. With the queue set to `sync`, emails are sent while the visitor waits.

1. Install Redis and make sure the app can reach it (`REDIS_HOST`, `REDIS_PASSWORD`).
2. Copy `deploy/supervisor/adniro-workers.conf` to `/etc/supervisor/conf.d/`, set the project path and user, then run `supervisorctl reread && supervisorctl update`.
3. Then switch Admin > Settings > Optimization > Queue driver to **Redis**.

Two workers run: one for emails, SMS, general jobs and thumbnails (`mail,sms,default,thumbs`), and one for AI checks (`ai`), so a slow AI call never delays an email. `deploy.sh` restarts them after each deploy.

## 2b. Live chat (Laravel Reverb)

The chat works without this step: it then checks for new messages every 10 seconds while a chat is open.
With it, messages, typing, "seen" and the unread badge are instant, and the 10-second check stops.
Everything here is free and runs on the same server; nothing is installed besides the Composer package.

1. `.env`: `BROADCAST_DRIVER=reverb`, and three values you make up once (letters and digits):
   `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`. Also
   `REVERB_ALLOWED_ORIGINS=www.adniro.com,adniro.com` and `REVERB_CLIENT_HOST=www.adniro.com` (so the address
   browsers connect to never comes from the request). Then `php artisan config:cache`.
2. The socket server: `deploy/reverb/adniro-reverb.service` (the steps are at the top of the file).
3. nginx: the two blocks in `deploy/reverb/nginx-reverb.conf`.
4. Cloudflare: Network > WebSockets = On.
5. Check: `systemctl status adniro-reverb`, then open a chat in two browsers; a message should appear at once
   and the "Reconnecting…" bar should never show.

After every deploy: `systemctl restart adniro-reverb`. To turn live chat off again, set `BROADCAST_DRIVER=null`
and `php artisan config:cache`; the chat falls back to checking by itself.

It holds about 1,000 open chats with the server's current settings. Before that many people are online at once,
install the free `ext-uv` PHP extension (`pecl install uv`) and restart the service.

The unread-messages email is sent by the scheduler (`chat:unread-digest`, every minute): no extra setup.

## 3. Scheduler (cron)

Add the line in `deploy/crontab` with `crontab -e` as the web user. It runs backups, the AI moderation sweep, the draft purge and other housekeeping every minute.

## 4. Deploying

After the new code is on the server:

```bash
bash deploy/deploy.sh
```

It puts the site in maintenance mode, installs Composer packages without dev tools, runs migrations (which also apply settings and page changes), caches routes, views and events, restarts the workers, and brings the site back up.

Config is deliberately **not** cached: LaraClassifier reads several `.env` values at runtime with `env()`, and they would be lost.

If an admin changes the listing URL format (Admin > Settings > SEO > permalinks), run `php artisan route:clear && php artisan route:cache`.

## 5. Health and monitoring

- **Health check:** `GET /health` answers `200` with `{"status":"ok"}` when the database, cache, storage and scheduler all work, and `503` otherwise. Point an uptime monitor (UptimeRobot, Better Stack, Pingdom…) at it, checking every 1–5 minutes. "scheduler: fail" means the cron line is missing or not running.
- **Logs:** `storage/logs/laravel-YYYY-MM-DD.log`, kept 14 days, plus `worker-default.log` and `worker-ai.log`.
- **Error tracking (optional):** for alerts on every error, add Sentry or a similar service: `composer require sentry/sentry-laravel`, then set `SENTRY_LARAVEL_DSN` in `.env`. Not installed by default because it needs an account.

## 6. Backups

Backups run daily at 03:00 (Admin > Settings > Backup). By default they stay on the same server, which does not protect against losing the server.

**Off-server copies:** in `.env`, set `FILESYSTEM_CLOUD=s3` and the `AWS_*` values for a bucket (S3, DigitalOcean Spaces, Wasabi, or any S3-compatible storage). Then choose **Local and Cloud** under Admin > Settings > Backup > Storage disk.

**Test a restore once, then every few months:**

1. Download the latest backup from Admin > Backups (or from the bucket).
2. Unzip it. The database dump is in `db-dumps/`.
3. Import it into an empty database: `mysql -u USER -p NEW_DB < db-dumps/mysql-*.sql`.
4. Point a copy of the site at that database and check that listings, users and photos are there.

Backups contain the AI provider keys, so only super admins can open Admin > Backups.

## 7. Upgrading LaraClassifier

Run `php deploy/core-changes.php` first. It lists every core file this site changed, deleted or added (`docs/core-changes.md`). Upgrade on a branch, compare each changed file, and run the tests (`php artisan test`).
