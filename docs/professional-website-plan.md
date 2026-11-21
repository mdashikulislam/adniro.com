# Making adniro.com a professional website

Written 2026-11-20 on branch `ai`. Settings values were checked on the local database (13 listings, 2 users). Check each one in production before acting on it.

Sources:
- a planner agent (whole site: design, flows, SEO, performance, operations)
- a code-review agent (the AI branch)
- a security-audit agent (the AI branch and site basics)
- a silent-failure agent (errors swallowed in the AI code)
- a browser review of the main pages at desktop and phone width
- my own list of mistakes made while building the AI features

## 1. My mistakes in this session

| # | Mistake | Effect | Status |
|---|---|---|---|
| 1 | Built AI posting without first checking classifedz.com, where the owner had already built it a different way | adniro and classifedz now work differently for the same feature | Open: owner to decide which way to follow |
| 2 | Encrypted API keys with `APP_KEY` | Keys became unreadable after `APP_KEY` changed, and saving a new key crashed with "The MAC is invalid" | Fixed: plain text at the owner's request |
| 3 | The evaluation counted failed calls as passes for violating listings | A run with no API key looked half successful | Fixed |
| 4 | Calls stopped before sending (no key) counted toward the daily call limit | 102 failed calls used today's allowance | Fixed |
| 5 | An editing slip emptied the composer template | Composer page returned an empty body | Fixed, guard test added |
| 6 | A multi-line array inside `@json(...)` | Composer page returned a 500 | Fixed, template parse test added |
| 7 | Tests rendered only parts of pages, and front-end tests skip the localization middleware | Two broken templates passed the tests | Partly fixed: template guard tests |
| 8 | Commit messages did not follow the owner's conventional format (`feat:`, `fix:`), and carried a Co-Authored-By trailer the owner's settings turn off | 12 commits with the wrong format | Open: follow the format from now on |
| 9 | Tests written after the code instead of first, and coverage never measured | Owner's rules ask for test-first and 80% coverage | Open |
| 10 | Several files exceed the owner's size guidance: `ModerationService` (600 lines), `composer.blade.php` (465), `LaravelAiClient` (454), `QuickPostController` (402) | Harder to maintain | Open: split |
| 11 | Translations into 8 languages were written by me, not by native speakers | Wording may be off in places | Open: native review |
| 12 | Real publishing, voice on real phones and the Firefox recorder were never tried | Unknown bugs may remain in those paths | Open |
| 13 | I said the evaluations had stopped when they had finished, because I misread the database clock (6 hours ahead of the stored times) | Wrong alarm, quickly corrected | Lesson noted |

## 2. Browser review (local site)

1. **No header on phones at first view.** At scroll position 0 the sticky navbar is moved off screen (`translateY(-75px)`, opacity 0) while the page loader (`pace`) keeps running more than 4 seconds. Visitors see no logo, menu or "Post an ad" until they scroll.
2. **Login nudge covers content.** The "Get the best deals faster / Log in / Create account" box overlaps the breadcrumb and buttons on every page.
3. **Test data on the home page.** "Afafasfs asdgasdgasd...", listings without photos (grey camera placeholders), a phone listed under "Houses & Apartments For Rent". Clean before launch.
4. **Generic hero.** "SELL AND BUY NEAR YOU / Simple, fast and efficient" over a blurred stock photo, all capitals.
5. **Weak social proof.** "12 classified ads, 2 trusted sellers" makes the site look empty. Hide the counters until they are meaningful.
6. **"Powered by LaraClassifier"** in the footer.
7. **Search relevance.** "phone" returns a screen-protector service and an eCommerce website, not phones.
8. **Raw values on cards.** Dates like "2026-11-20 09:59", "Contact us" in place of a price.
9. **Listing meta description** is the listing's raw text (gibberish for the test listing).
10. **Page weight.** The home page loads 26 scripts.

## 3. Silent failures in the AI code

The silent-failure agent's main findings, verified against the code where marked.

High:
1. **Rejection email marked sent when it was not.** `notified_at` is set as soon as the notification is queued, even when no channel applies (phone owners without an SMS driver) or the queued job later fails. Never retried.
2. **Admin "Reject" shows success** even when the owner was not told.
3. **Enforce mode can fail open.** If marking a listing pending throws, the listing is saved as public and never checked.
4. **Unreadable photos are dropped silently.** Moderation then tells the model "no photos" and may approve on text alone.
5. **Urgent child-safety alerts can fail with no record.**

Medium:
- **Settings fallback (verified).** A database error while reading AI settings returns the defaults: AI off and "publish when unavailable". The sweep would then publish waiting listings unchecked.
- **Budget not enforced without prices.** Models without prices make the budget never trip, and the spend widget shows $0.
- **Spend widget hides errors (verified).** It disappears on any error, with no log.
- **Sweep stops on one bad listing.** Every later run hits the same listing, and the rest stay pending.
- **Lost drafts.** A failure after a successful AI draft returns a 500 and loses the draft.
- **Misleading API message.** The API says quick post is "off" when the AI actually failed.

Low: unlogged photo upload errors, the composer timeout racing the server, purge and cache errors not counted, a stale catalog for up to a day.

## 4. Code review of the AI branch

Ranked most severe first. Items 1 and 3 were re-checked against the code.

High:
1. **Free re-drafts chain forever (verified).** Only the immediate parent's re-draft count is checked, and each new draft starts at zero. A member at "0 left" can keep drafting through "Describe it again", and pay nothing. Carry the count down the chain, and check it inside the lock.
2. **Week and month limits stop working.** The limit counts rows in `post_drafts`, but the purge deletes them after 48 hours. Keep a small ledger of counted uses, or purge only the heavy columns.

Medium:
3. **Limit window shifted by time zone (verified in code).** The window start is converted to UTC, while `created_at` is stored in the app's time zone. On a server set to Asia/Dhaka, the daily limit would reset at 06:00 local time. Locally the app runs in UTC, so it doesn't show yet.
4. **Unchecked photos.** Moderation sends only the first 5 photos, and silently skips unreadable ones. It also matches silent-failure finding 4.
5. **Budget exhaustion publishes everything.** Spending the daily budget through drafts makes moderation publish everything when "AI unavailable" is set to publish. Reserve a budget for moderation, or hold listings when capped.
6. **Pending listings released too easily.** The sweep releases them whenever enforce drops out, including when a newly assigned model hasn't been evaluated yet.
7. **Job time-out too short.** The moderation job times out at 120 seconds, while one check can take up to 4 calls of 60 seconds.
8. **Re-run silently dropped.** Admin "Re-run" does nothing when a check is already queued, but says "queued".
9. **Repeated "online" email.** The email can repeat for listings that went live before moderation existed, or during shadow mode.
10. **Settings read on every call.** The AI settings are read from the database on every call, adding many queries per listing save and on every page with a "Post an ad" link. Memoize per request or per job.

Low:
- **Edit overwritten by a decision.** An edit saved during the instant a decision is applied can be overwritten.
- **Override ignored with no limit.** A per-user override doesn't apply when the site period is "no limit".
- **Counts include hidden listings.** Category and seller counts include them, though the listings themselves stay hidden.
- **Dates removed as phone numbers.** The phone-number filter removes dates and serial numbers.
- **Photo uploads not rate limited.** This duplicates security M4.
- **Old decisions reused after policy changes.** Decisions are reused across policy versions, and editing only the city never triggers a re-check.

## 5. Security audit

There are no critical or high findings in the AI code. Access control, uploads, draft ownership, output encoding and hidden-listing checks hold up on a code read. Nothing was exploited live.

Medium:
- **M1. Unredacted errors reach staff.** `ModerationService` stores raw exception text in `post_moderations.error`, and staff with "edit listings" see it in the queue and history. Run it through the client's redactor, and widen that redactor to cover `AIza...` keys, `key=` and `Authorization: Basic`.
- **M2. Plain-text keys are in every database backup.** Staff with the backup permission can download them. Limit backups to super admins, and encrypt backup archives.
- **M3. Spoofed IPs bypass guest limits.** `TrustProxies` trusts every proxy (`*`), so a forged `X-Forwarded-For` header resets the per-IP guest limits and burns the AI budget. Trust only the real proxy, and add a global guest cap. Guests are off by default.
- **M4. Photo uploads have no rate limit.** The 5-photo cap lives in the session, so dropping the cookie allows unlimited uploads. Add a rate limit per IP and per user.

Low:
- **L1. HTML in admin toasts.** They render HTML, and the listing title goes in without `e()`.
- **L2. Markdown links in emails.** Alert and rejection emails can contain markdown links taken from a listing title.
- **L3. Apply uses GET.** The draft apply action changes state on a GET. It's harmless with 32-character tokens, but cleaner as a POST.
- **L4. No security headers.** `.htaccess` removes `X-Frame-Options`, and there is no CSP, HSTS, `nosniff` or `Referrer-Policy`, so the admin panel can be framed.
- **L5. AI limits on super admins.** Staff with "edit users" can change a super admin's AI limit.
- **L6. Dependency advisories.** `composer audit` reports high advisories in `phpseclib/phpseclib` and `zbateson/mail-mime-parser`, plus others.
- **L7. Production settings.** Production needs `APP_ENV=production` and `SESSION_SECURE_COOKIE=true`. SMTP passwords stored in the `settings` table deserve the same check as the AI keys.

## 6. Site-wide findings (planner agent, key settings verified locally)

1. **CSRF protection is off (verified).** `security.csrf_protection = 0` makes every request skip the CSRF check.
2. **The phone header is hidden by a setting (verified).** `header.default_animation = 1` explains it.
3. **No seller can show as verified (verified).** The badge needs email and phone verification, and both are switched off.
4. **Raw dates (verified).** The date format is `YYYY-MM-DD HH:mm`, and "time ago" display is off.
5. **Analytics without consent (verified).** Google Analytics loads on every page, and the cookie banner is off. European country sites are active.
6. **Boilerplate pages.** The FAQ claims listings expire after 3 months (they don't), the anti-scam page is stock text, and the privacy policy doesn't mention AI processing or Analytics. `/page/about-us` returns 404.
7. **Stock error pages.** They have no header, footer or search, and the 404 page prints the framework's message ("The route ... could not be found").
8. **Heavy pages.**
   - About 700 KB of CSS and 825 KB of JavaScript are loaded.
   - The JavaScript bundles jQuery 3.3.1, which has known XSS advisories.
   - The `pace` loader blocks rendering.
   - `/posts/create` loads select2 three times.
   - Card images say "lazy load" but load at once, with no size set, so the layout shifts.
9. **SEO details.**
   - Report pages can be indexed.
   - The contact page title is "Adniro - adniro".
   - Open Graph image sizes are wrong.
   - The Organization data has no social links.
   - Headings skip levels.
10. **Operations (verified).**
    - The queue runs in sync mode, so emails send inside the request.
    - No backups are scheduled.
    - There is no health check or error tracker, and logs are kept for only 2 days.
    - There is no favicon or app icon.
    - Only the AI code has tests.

Also: the "Save" control on cards can't be reached by keyboard, the search box has no label, and all 16 country sites are live although every listing is in the US.

## 7. The plan

Each phase can ship on its own. The effort is for one developer.

### Phase 1: settings and data only, no code (about 1 day)

> **Status, 20 Nov 2026: done, except the items marked "owner".** The settings are applied by the migration `2026_11_20_001100_apply_professional_site_settings.php`, so they reach the live site on deploy and can be rolled back. CSRF was checked in the browser: login, the login popup and the quick post calls work, and requests without a token are refused. Open: captcha (both Turnstile keys are set; switch it on in Admin > Settings > Security and try a login, since a key for the wrong domain blocks every form), the slogan and home meta text, deleting the test listings (owner), and payment returns (no gateway set up locally).
1. **Security.** Switch CSRF protection back on (`security.csrf_protection = 1`), then click through login, the login popup, quick post and payment returns. Turn on captcha for registration, contact and guest posting.
2. **Header.** Turn off the header animation (`header.default_animation = 0`). This fixes the hidden phone header.
3. **Dates.** Choose a readable date format, and turn on "time ago" for listings.
4. **Copy and trust.**
   - Hide "Powered by LaraClassifier".
   - Set a real slogan, and write proper home meta tags.
   - Hide the home counters until the numbers are meaningful.
   - Delete the test listings.
5. **SEO and images.**
   - Stop report pages from being indexed.
   - Turn on WebP for new uploads, at quality 80 to 85.
   - Turn on email verification, so sellers can earn the verified badge.
6. **Backups.** Schedule a daily backup, and limit backup downloads to super admins (security M2).

### Phase 2: fix the AI branch before anything is switched on (3 to 4 days)

> **Status, 20 Nov 2026: items 1 to 7 done, with tests (203 passing).** Extra find while testing: the Picture model swaps a missing photo for the placeholder image, so the AI was "checking" the placeholder. Moderation now reads the raw paths. Changes worth knowing:
> - Free re-drafts are counted on the first counted draft, shared by its whole chain, and checked inside the lock. A failed draft gives no free re-drafts.
> - The purge empties counted drafts and keeps them 35 days. A per-user limit on a site without a limit counts per day.
> - Budget: drafts and voice stop at 80% of the daily budget and call limit, moderation can use all of it. Over budget, listings go to the review queue.
> - Photos that could not be checked send the listing to the queue (rule `photos_not_checked`).
> - Sweep: does nothing if the settings can't be read, hands listings to admins when enforce is chosen but no model passed the evaluation, and skips one broken listing instead of stopping.
> - Rejection emails are sent at once and marked sent only when they went out. The admin is warned otherwise. Failed alerts are logged (critical for child safety).
> - Job time-out 300 s, Redis `retry_after` 330 s (set `REDIS_QUEUE_RETRY_AFTER=330` on the server). Re-run has its own lock.
> - Security: errors are cleaned of keys before staff see them, `TRUSTED_PROXIES` (default `*`, set it to the real proxy on the server), a site-wide daily cap for guest drafts (200), photo and voice upload limits, escaped titles in toasts and plain titles in emails, super admins' AI limits and backups for super admins only.
> - The phone-number filter needs 9 digits, so dates and years stay in AI text.
> - The AI settings are read once per request or job, with a saved last-good copy.
>
> Still open: item 8 (no coverage tool installed: add pcov or xdebug), encrypting backup archives, and the silent failures not listed here (budget not enforced for models without prices, spend widget errors).

Most important first:
1. **Re-draft chain and week/month limits.** Stop free re-drafts from chaining (code review 1), and keep the AI post count beyond 48 hours so week and month limits work (code review 2).
2. **Unchecked photos.** Never approve a listing with unchecked photos. Hold it for review if any photo can't be read or there are more than 5 (code review 4, silent failure 4).
3. **Fail closed.** If settings can't be read, hold listings instead of publishing them. Release waiting listings only when an admin switches enforce off. Reserve a moderation budget, or hold listings when the budget is spent (code review 5 and 6, silent failure "settings fallback").
4. **Rejection emails.** Record the email as sent only when it actually went out. Warn the admin when it didn't, and log failed admin alerts (silent failure 1, 2 and 5).
5. **Time zone and timing.** Fix the limit window's time-zone shift, raise the moderation job time-out, and make Re-run work while a check is queued (code review 3, 7 and 8).
6. **Security fixes.**
   - Redact errors shown to staff (M1).
   - Trust only the real proxy (M3).
   - Rate-limit photo uploads (M4).
   - Escape titles in toasts and emails (L1, L2).
   - Protect super admins' AI limits (L5).
7. **Performance.** Remember the AI settings for the length of a request (code review 10).
8. **Test gaps.** Close the gaps from my mistakes list: real HTTP tests for the composer and review pages, and a coverage report.

### Phase 3: quick front-end wins (3 to 4 days)

> **Status, 21 Nov 2026: done, with tests (210 passing) and checked in the browser.**
> - **Images:** card pictures use the browser's own lazy loading, with width and height set. The old swap only covered `<img>`, so with WebP on (phase 1) every WebP thumbnail loaded at once. The `pace` loader is gone.
> - **Error pages:** branded 404, 403, 401, 410 and 500 pages with logo, search (on not-found pages), home and back links, and footer. They never show framework text, and server error details only appear while debugging. The 403 and 500 pages wrongly said 401.
> - **Scripts on the posting form:** down from 26 to 18. Shared libraries (select2, moment, fileinput, daterangepicker) were pushed once per file because `@pushonce` had no shared ID; `browse.locations.js` loaded twice; payment scripts load only when packages are for sale.
> - **SEO and accessibility:**
>   - Heading order on the home page; an `h1` on every posting form page.
>   - Listing share images no longer claim a wrong 1200x630 size.
>   - The Organization data lists the site's social profiles.
>   - Save on cards works by keyboard; the search boxes have labels; there is a skip link.
> - **Icons:** `/favicon.ico`, `/apple-touch-icon.png`, `/icon-192.png`, `/icon-512.png` and `/manifest.json` are made from the favicon in Admin > Settings, with the brand colour as theme colour.
> - **Login nudge:** shown once per browser session instead of on every page.
>
> Left for the owner: a default share image (Admin > Settings > Social share; the home page has none), and the slogan, which is why the contact page title reads "Adniro - adniro".
- **Images.** Real lazy loading and fixed image sizes on listing cards, and remove the `pace` loader.
- **Error pages.** Branded 404 and 500 pages with header, footer and search, never showing the framework's message.
- **Duplicate scripts.** Stop them loading twice on the posting form, and load payment scripts only when packages are for sale.
- **SEO and accessibility.**
  - Proper heading order, and an `<h1>` on the posting form.
  - Correct Open Graph image sizes, and social links in the Organization data.
  - A keyboard-reachable Save button, a label on the search box, and a skip link.
- **Small items.** Favicon, app icon and `manifest.json`. Make the login nudge smaller, and keep it from covering content.

### Phase 4: trust and safety (4 to 6 days)

> **Status, 21 Nov 2026: done, with tests (216 passing).**
> - **Seller card:** "Verified seller" badge for a confirmed email (decision 2; login rules unchanged), and "Active listings" counting only listings the public can see. Moderation-held and unreviewed listings no longer count on seller pages either.
> - **Safety:** two more tips on listings (no deposits, never share codes); "Know more" now works. A "Report abuse" link sits next to the reference under the price.
> - **Safety page:** the anti-scam page is the site's "tips" page with a current guide (migration; owner-edited text would be kept; English only, other languages show it until translated).
> - **Messages:** a short safety reminder at the top of every conversation and in the "Send a message" popup.
> - **Emails:** brand colour (theme `adniro`), logo with alt text, and Help, Safety tips and Email preferences links plus a "why you got this" line.
> - New texts in all 9 languages; "Verified seller" was English only before.
>
> - **Messenger restyle** (checked signed in, in Chrome, with a temporary test conversation that was removed afterwards): the listing card (photo, title, price, link) at the top of a conversation, own messages in the brand colour, larger message text, small round avatars, a rounder message box, and unread conversations tinted with an accent bar instead of yellow.
- **Seller card.** A verified badge, "member since" and an active listing count on the listing page.
- **Safety and reporting.** A safety tips card that links to a rewritten anti-scam page, and a "Report" link next to the price.
- **Messages.** Restyle the messenger, with a safety banner in each conversation.
- **Emails.** Branded emails: logo, brand colour, help and safety links, and an unsubscribe or preferences link.

### Phase 5: content and legal (2 to 3 days, mostly writing)

> **Status, 21 Nov 2026: done, with tests (222 passing) and checked in the browser. The texts are drafts in English: have them read by someone qualified before relying on them legally.**
> - **Cookie consent:** with the cookie banner on (now switched on), the footer tracking code (Google Analytics) sits inert in a `<template>` and runs only after "Accept all". The banner is shown by `consent.js` in the browser, so cached guest pages work. "Cookie settings" in the footer reopens it. It replaces the server-side `cookie-consent` banner.
> - **New pages:** About us, Posting rules and Cookie policy, linked in the footer and the page sidebar.
> - **FAQ:** the stock text (wrong 3-month expiry) is replaced with current answers: 30 days online, 7 days to renew, the AI box, listing checks, contact privacy, verified sellers, reporting, account deletion.
> - **Privacy policy:** your text is kept. Added "AI Features" (listing checks, the AI box, voice input) and "Analytics and Cookies", retention times in "Data Retention", renumbered the last two sections and updated the date.
> - Texts live in `database/data/pages/`; the migrations only replace stock text, and `down()` restores what was there.
>
> Hard-coded facts to keep in step with the settings: 30 days online, 7 days to renew, up to 5 photos, and "Anthropic" as the AI provider. Other languages show the English text until translated.
- **Pages.** Write About, a correct FAQ (including how AI moderation and quick post work), posting rules, a cookie policy, and a privacy policy that covers the AI providers, voice input, Analytics, retention and deletion.
- **Cookie consent.** Load Analytics only after consent. The banner must be decided in JavaScript, because the guest page cache serves one copy of each page to everyone.

### Phase 6: performance (3 to 5 days)

> **Status, 21 Nov 2026: done, with tests (228 passing) and a click-through** (home, search, listing page, posting form with category, custom fields, city popup, editor and tags; signed in: account profile, admin dashboard, admin listings table, admin settings form). No console errors.
> - **jQuery 3.3.1 → 3.7.1** in the front, auth and admin bundles. The bundles were rebuilt with `npx mix --production --mix-config=webpack.mix.combine.js`; CSS output is byte-identical, JS differs only by the newer minifier (no source had changed).
> - **One notification library:** flash messages use SweetAlert2 toasts (same corner, same 10 s); PNotify is gone from the front and auth bundles (front scripts 826 KB → 745 KB). The admin panel still uses PNotify directly in about 13 places, so its bundle keeps it.
> - **custom.css** now lives in `resources/css/front/custom.css` and is built minified and versioned (243 KB → 200 KB). **Edit the source, then run the combine build above**; `public/dist/front/custom.css` is generated.
> - **Search:** keyword results are ranked by relevance before date (relevance only broke ties before), and a title or category match weighs more than a mention in the description, so "phone" finds phones first. Sorting by date or price still works.
> - **Editor:** already loaded only for the description field; nothing to change.
> - **Static file caching:** Apache rules in `public/.htaccess` (images 30 days, fonts a year, other CSS/JS 7 days) and `public/dist/.htaccess` (a year, immutable; these URLs carry a content hash). On nginx, the equivalent is:
>   `location ^~ /dist/ { add_header Cache-Control "public, max-age=31536000, immutable"; }` and
>   `location ~* \.(jpe?g|png|webp|gif|svg)$ { expires 30d; }`, `location ~* \.(woff2?|ttf)$ { expires 1y; }`, `location ~* \.(css|js)$ { expires 7d; }`.
>
> Noticed, not changed: listing titles are capitalised by the site ("iPhone" shows as "IPhone").
- **Custom CSS.** Move `custom.css`, currently 243 KB and built by hand, into the build, so it is minified and versioned.
- **jQuery.** Upgrade to 3.7, and drop unused libraries. Use one notification library: PNotify or SweetAlert2. This needs a full click-through.
- **Editor and caching.** Load the rich text editor only for the description field, and set long cache headers for static files.
- **Search relevance.** "phone" should find phones.

### Phase 7: operations and upgrade safety (3 to 4 days)

> **Status, 21 Nov 2026: done in the code; the server steps are in `docs/operations.md`. Tests: 236 passing, no warnings.**
> - **Health check:** `GET /health` answers 200 or 503 with database, cache, storage and scheduler checks (the scheduler writes a heartbeat every minute). Locally it reports "scheduler: fail" because no cron runs here.
> - **Logs** kept 14 days (was 2).
> - **Deploy:** `deploy/deploy.sh` (maintenance mode, composer, migrations, route/view/event caches, worker restart). Config is not cached on purpose: LaraClassifier reads `.env` with `env()` at runtime.
> - **Queue:** `deploy/supervisor/adniro-workers.conf` with two workers: `mail,sms,default,thumbs` and `ai` (a `default`-only worker would never have sent emails). `deploy/crontab` for the scheduler. Switch the queue driver to Redis in the admin once the workers run.
> - **Backups off the server:** no code needed; set the cloud disk in `.env` and choose "Local and Cloud" in Admin > Settings > Backup. Restore steps are in the guide.
> - **Packages:** phpseclib, zbateson/mail-mime-parser, symfony/http-client, phpunit and psysh updated (patch and minor versions). One low advisory remains on firebase/php-jwt, which has no fixed 6.x release and is required by Socialite.
> - **Upgrade safety:** `php deploy/core-changes.php` writes `docs/core-changes.md` (stock files changed, deleted and added). Smoke tests for home with structured data, a listing page, search, a CMS page, the listing form, the 404 page and the sitemap, on the stock seed data.
>
> Not done: moving new views into a theme override folder (low value for the risk; the core-changes list covers upgrades), and an error tracker (needs an account; how to add Sentry is in the guide).
- **Queue.** Use Redis, with a Supervisor worker for `default` and `ai`, plus the scheduler cron.
- **Monitoring.** Turn on the health check, add uptime monitoring and an error tracker such as Sentry, and keep logs for 14 days or more.
- **Deploys and backups.** A deploy script with caches, `queue:restart` and `APP_ENV=production`. Daily off-server backups, with one test restore.
- **Dependencies.** Update the packages with advisories (security L6).
- **Upgrade safety.**
  - Write the list of every changed core file.
  - Move new views into the theme override folder.
  - Add feature tests for the home, listing, search, 404, sitemap and structured-data pages.

## 8. Decisions (answered 20 Nov 2026)
1. **AI posting.** Port the classifedz.com AI box into the normal posting form. adniro's moderation, limits, budget and evaluations stay behind it; the separate composer page goes away. **Done 21 Nov 2026:** the box sits at the top of step 1 (and of the single-step form), fills the form in place through `POST posts/create/quick/fill`, and has undo, a checklist, example chips, voice (recording when a speech-to-text model is assigned, otherwise the browser's dictation) and texts in all 9 languages. `posts/create/quick` now redirects to the form. Photos are no longer sent to the AI from the web (the box runs before the photos step); the mobile API still accepts them.
2. **Verified badge.** Email only. No SMS provider needed; phone verification stays off.
3. **Countries.** The live server has 6 countries; keep those. The 16 local ones don't matter.
4. **Guest posting.** Left to the admin: Admin > Settings > Listing form > "Guests can submit listings", and for AI drafts, Settings > AI > Quick post > guests. Captcha covers guests once it is switched on.
5. **AI crawlers.** Keep GPTBot, ClaudeBot and Google-Extended blocked in `robots.txt`.
6. **Notifications library.** Keep SweetAlert2 only (pop-ups and toast mode); remove PNotify, which is used in 18 files today.
7. **Commits.** Conventional format (`feat:`, `fix:`) without the Co-Authored-By line.

## Total
About 20 to 28 developer days. Phases 1 and 2 matter most: phase 1 is settings only, and phase 2 should be done before AI moderation or quick post is switched on for real users.
