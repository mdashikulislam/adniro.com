# Architecture: Adniro Live Chat (replaces the old "send message" Messenger)

The goal: a **live chat** that feels like WhatsApp or Marketplace. Tap **Chat** on a listing, type, and the other
person sees it instantly, with typing, "seen" ticks, online status, photos and quick replies. Phones first.

Three rules from the owner (2026-11-23):

1. **No cost.** No paid service, no per-message fees, nothing that bills as the site grows.
2. **The website must not get slower.** Chat must not take PHP workers, database time or page weight from the
   rest of the site.
3. **The old feature can go.** It is replaced, not kept beside the new one.

Recon: the current code was mapped end to end and the live server checked (2026-11-22 and 2026-11-23).
Live usage: **1 conversation, 6 messages, none in 30 days**, so replacing it carries almost no data risk.

## What exists today, and why it goes

| area | today | problem |
| --- | --- | --- |
| Start a chat | modal form on the listing (`front/account/messenger/modal/create`), captcha | Feels like a contact form. Every message makes a **new** conversation, even with the same seller about the same listing. |
| Inbox and conversation | `account/messages` pages; the open chat reloads every 60 s | Up to a minute late; stacks badly on phones; "seen" ticks work backwards. |
| Unread badge | `public/assets/js/script.js` asks `/account/messages/check-new` every 60 s on **every page** for every signed-in tab | **573 PHP requests in 8.5 hours on live** just to ask "anything new?", almost always "no". Sockets remove this. |
| Email | one email per message (`SellerContacted`, `ReplySent`), sent inside the request | Noisy, and slows the send. |
| API | `/api/threads/*` for the LaraClassifier phone app | Adniro has no phone app; nothing calls it. Shares the same insecure code. |

**Security holes in the old code** (both disappear when it is removed, and get a two-line patch now so they are
closed while the new chat is built):

1. **Anyone signed in can reply into anyone's conversation.** `ThreadService::update()` uses
   `Thread::findOrFail($id)` with no participant check; `ReplyMessageRequest::authorize()` only checks sign-in.
2. **Attachments are public** through `/common/file?path=` (`FileController::watchMediaContent`).

## Cost: everything is free

| part | what it is | cost |
| --- | --- | --- |
| **Laravel Reverb** | Laravel's own WebSocket server, open source (MIT), runs on our server | $0 |
| **Laravel Echo + pusher-js** | browser libraries that talk to Reverb (pusher-js is only the client code; **no Pusher account**) | $0 |
| **systemd** | keeps Reverb running, restarts it if it stops; already on the server, nothing to install | $0 |
| **nginx, Redis, MySQL, cron** | already running | $0 |
| **Cloudflare** | WebSockets are included on the free plan | $0 |
| **Email digest** | the existing mail setup | $0 |
| Later: phone push | Web Push with our own keys (VAPID), no service in between | $0 |

Not used: Pusher, Ably, Firebase (all bill as usage grows), SMS.

**Why Reverb and not Socket.io:** Socket.io needs a separate Node app, its own login check and a bridge to
Laravel (the old `laravel-echo-server` bridge is abandoned). Reverb is PHP, made by Laravel, uses the same
sign-in and the same channel rules as the site, and Laravel Echo supports it directly. It is the same idea, a
socket server, with less to build and maintain.

## How it stays fast

The server today: 6 cores, 12 GB RAM (10.5 GB free), 24 PHP-FPM workers, Redis, PHP 8.3 with pcntl and sockets.

1. **Reverb is its own process, not a PHP-FPM worker.** An open chat holds one socket in Reverb (a few KB of
   memory each), never a PHP worker. The 24 workers stay free for pages. Expected use: 50–150 MB RAM and a
   fraction of one core for hundreds of people online.
2. **Fewer PHP requests than today.** The 60-second "anything new?" poll on every page is deleted. New messages
   and the unread badge are pushed through the socket instead.
3. **Typing and online status never touch PHP or MySQL.** Typing is sent browser to browser through Reverb
   ("whispers"); online status lives in Reverb's memory (a presence channel). The only PHP call is one quick
   permission check when a chat opens.
4. **Sending is one short request.** Save the message (one insert, one update), then hand it to Reverb on
   localhost (about 2 ms). No email is sent during the request.
5. **Email is batched.** A scheduled job, every minute through the existing cron, sends one "you have unread
   messages" email to people who haven't read for 10 minutes, at most once an hour per conversation.
6. **Cheap queries.** The inbox reads one indexed list sorted by `last_message_at`, 20 at a time. History loads
   30 messages at a time by id. Unread counts are stored numbers, never counted on page load.
7. **Nothing extra for visitors.** Guests and search bots get no chat code and no socket. Signed-in users get one
   small script (about 25 KB compressed), loaded after the page has finished, and the socket connects only then.
   Chat page code loads only on chat pages.
8. **Idle tabs disconnect.** A tab hidden for 5 minutes closes its socket and reconnects when it's shown again,
   so forgotten tabs cost nothing.
9. **Photos are shrunk in the browser** before upload (max 1600 px, JPEG/WebP), so uploads are small and fast;
   the server stores one copy plus one thumbnail.
10. **If the socket can't connect** (a strict office network, say), the open chat checks every 10 s, only while
    the tab is visible, using a tiny `?after=<last id>` request that returns "nothing new" in a few ms. The rest
    of the site never polls.
11. **Limits.** Reverb handles about 1,000 people online at once with the server's current settings. Before
    reaching that, raise the open-files limit (in the systemd unit) and install the free `ext-uv` PHP extension.

## Stack

| layer | choice | why |
| --- | --- | --- |
| web | Laravel 12 + Blade + Bootstrap 5.3, a small plain-JavaScript chat module in `public/assets` | Same as the rest of Adniro; no new build step. |
| realtime | Laravel Reverb behind nginx at `wss://www.adniro.com/app`, Laravel Echo in the browser | Free, self-hosted, same sign-in as the site. Same domain and port 443, so Cloudflare passes it with no extra setup. |
| database | the existing MySQL tables `threads`, `threads_messages`, `threads_participants`, upgraded, plus 2 new tables | Keeps the 6 existing messages; no second database. |
| auth | the site's session sign-in; private and presence channels allowed only for the people in that conversation | One permission rule for pages, data and sockets. |
| files | private storage, served only through a signed link checked against the conversation | Fixes the public-attachment hole. |
| jobs | the existing cron (`schedule:run`); queue stays `sync`; messages pushed with `ShouldBroadcastNow` to localhost | No queue worker to run. |
| process | `adniro-reverb.service` (systemd, `Restart=always`, `LimitNOFILE=10000`), restarted on each deploy | Nothing to install. |

## Schema

Tables: **3 upgraded + 2 new**. Access rule: **a participant check in the data layer on every query**, plus
channel authorisation for sockets (MySQL has no row-level security).

```sql
-- 1) One conversation per (listing, buyer): find it or create it, never a new one per message
ALTER TABLE threads
  ADD COLUMN buyer_id BIGINT UNSIGNED NULL AFTER post_id,
  ADD COLUMN last_message_id BIGINT UNSIGNED NULL,
  ADD COLUMN last_message_at TIMESTAMP NULL,            -- the inbox sorts on this, no join
  ADD UNIQUE KEY threads_post_buyer_unique (post_id, buyer_id),
  ADD INDEX threads_last_message_at_index (last_message_at);

-- 2) Messages: a type, and one id per tap so a double tap or a retry sends once
ALTER TABLE threads_messages
  ADD COLUMN type ENUM('text','image','offer','system') NOT NULL DEFAULT 'text' AFTER user_id,
  ADD COLUMN client_uuid CHAR(36) NULL,
  ADD UNIQUE KEY threads_messages_client_unique (thread_id, client_uuid),
  ADD INDEX threads_messages_thread_id_id_index (thread_id, id);

-- 3) Per-person "seen" and unread count, stored, never counted on page load
ALTER TABLE threads_participants
  ADD COLUMN last_read_message_id BIGINT UNSIGNED NULL,
  ADD COLUMN unread_count INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN muted_until TIMESTAMP NULL,
  ADD UNIQUE KEY threads_participants_thread_user_unique (thread_id, user_id),
  ADD INDEX threads_participants_user_unread_index (user_id, unread_count);

-- 4) Block a person (checked both ways on every send)
CREATE TABLE user_blocks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  blocker_id BIGINT UNSIGNED NOT NULL,
  blocked_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY user_blocks_pair_unique (blocker_id, blocked_id),
  INDEX user_blocks_blocked_index (blocked_id),
  CONSTRAINT user_blocks_blocker_fk FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT user_blocks_blocked_fk FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5) Report a conversation to the admins
CREATE TABLE thread_reports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  thread_id BIGINT UNSIGNED NOT NULL,
  reporter_id BIGINT UNSIGNED NOT NULL,
  reason ENUM('spam','scam','abuse','other') NOT NULL,
  note VARCHAR(500) NULL,
  status ENUM('open','reviewed','dismissed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  INDEX thread_reports_status_index (status),
  CONSTRAINT thread_reports_thread_fk FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
  CONSTRAINT thread_reports_reporter_fk FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
);
```

A backfill migration fills `buyer_id`, `last_message_*`, `last_read_message_id` and `unread_count` for the
existing conversation and merges duplicate (listing, buyer) conversations. Times are stored in UTC and shown in
the reader's time zone. The live database is backed up before the migration.

## Routes

All behind sign-in and a participant check unless noted. JSON routes use the session and CSRF.

| method path | does | who | input | output |
| --- | --- | --- | --- | --- |
| GET `/chat` | inbox (phones: the list; desktop: list + open chat) | member | `?filter=all\|unread` | HTML |
| GET `/chat/{thread}` | conversation (full screen on phones) | participant | — | HTML |
| POST `/chat/listings/{post}` | find or create the conversation with the seller, with an optional first message | member, not the seller, not blocked | `body?`, `client_uuid` | `{thread_id, url}` |
| GET `/chat/api/threads` | inbox data, newest first | member | `cursor` | JSON |
| GET `/chat/api/threads/{thread}/messages` | 30 older messages, or new ones after an id (fallback) | participant | `before` or `after` | JSON |
| POST `/chat/api/threads/{thread}/messages` | send text or a photo, once per `client_uuid` | participant, not blocked, rate-limited | `body` (1–2000) or `image`, `client_uuid` | JSON + push |
| POST `/chat/api/threads/{thread}/read` | mark read up to a message | participant | `message_id` | push "seen" |
| POST / DELETE `/chat/api/users/{user}/block` | block / unblock | member | — | `{}` |
| POST `/chat/api/threads/{thread}/report` | report the conversation | participant | `reason`, `note?` | `{}` |
| GET `/chat/files/{message}` | open a photo (signed link) | participant | signature | file |
| POST `/broadcasting/auth` | allow a socket channel | member | channel | auth |

Channels: `private-chat.user.{id}` (inbox changes and the unread badge on every page) and
`presence-chat.thread.{id}` (messages, "seen", who is online; typing as whispers, no server call).
Job: `chat:unread-digest` every minute. Old `/account/messages/*` links redirect to `/chat`.

## What gets removed

- The contact modal on the listing (`front/account/messenger/modal/create`) and its "Send message" button,
  replaced by **Chat with seller**.
- The `account/messages` pages, their views and JavaScript, and the account-menu entry (it becomes **Chats**).
- The 60-second `check-new` poller in `public/assets/js/script.js`, its route and the `timer_new_messages_checking`
  setting.
- The per-message emails `SellerContacted` and `ReplySent`, replaced by the digest.
- The `/api/threads/*` routes (no app uses them).
- Kept: the three tables and their data, upgraded in place.

## The parts that bite

- **Security:** a participant check on every read, send, file and channel; all text escaped (links made
  clickable safely, no HTML); photos only through signed links.
- **Duplicates:** `client_uuid` is unique per conversation, so a double tap or a retry on a bad connection
  sends once.
- **Races:** two first messages at once can't make two conversations (unique listing + buyer); "seen" only moves
  forward.
- **The socket server:** systemd restarts Reverb if it stops, and every deploy runs `reverb:restart`.
  Cloudflare closes sockets silent for 100 s, so Echo pings every 30 s. If the socket fails, the open chat falls
  back to the 10 s check.
- **Spam:** send limits per member (about 20 a minute and 300 a day, lower for new accounts), the existing
  blocked-words list, block and report, and the existing AI moderation can review reported chats.
- **Scams:** a gentle warning when a first message has a phone number, email, link or "pay outside" wording;
  the safety tips link stays; nobody's contact details are shown unless they share them.
- **Photos:** images only (jpg, png, webp, heic, up to 8 MB before shrinking), location data (EXIF) removed.
- **Phones:** full-height chat (`100dvh`), the message box sits above the keyboard and the safe area, the app bar
  is hidden inside a conversation, and back returns to the inbox.
- **Time zones:** stored in UTC, shown local, with "Today" and "Yesterday" separators.
- **Account deletion:** deleting a member removes their messages, blocks and reports.
- **Guests:** chat needs an account; tapping Chat as a guest opens sign-in and returns to the chat.

## Build order

0. **Close the two holes now**: a participant check on reply and on attachments, plus a rate limit. A small
   change, shipped on its own, while the new chat is built.
1. **Basic chat working end to end:** Chat with seller → one conversation per listing and buyer → full-screen
   chat page → send and receive text → inbox. Migrations and backfill. Uses the 10 s check for now.
2. **Live:** Reverb on the server (systemd unit, nginx `/app` route), Echo in the browser; instant messages,
   typing, "seen" ticks, online status, the unread badge pushed to every page. The old 60 s poller is deleted.
3. **Must-haves:** photos, quick replies ("Is this still available?", "What's your best price?", "Where can we
   meet?"), the listing card pinned at the top, block and report, the unread email digest, then **remove the old
   Messenger** and redirect its links.
4. **Later:** make-an-offer, mark as sold from the chat, mute, inbox search, free Web Push notifications, an
   admin page for reports.

Each step is built and checked on local (127.0.0.1:8000) first, then deployed to live when the owner says so.
Step 2 is the only one that changes the server: one systemd service and one nginx block, both reversible.

## Defaults, unless the owner says otherwise

- **Guests:** must sign in to chat.
- **Bottom app bar:** **Chats** with an unread badge takes Saved's place; Saved moves into the account menu.

## As built (branch `chat`, 2026-11-23)

**Added after the owner tried it: replying to one message.** `threads_messages.reply_to_id` (migration
`2026_11_23_000500`); the reply carries a short quote, and only a message of the same conversation can be quoted.

**Changed after the owner tried it: one conversation per two people, not per listing and buyer.** A seller with
several listings appeared several times in the same person's inbox. Now two people have one conversation;
"Chat with seller" on another listing opens it with that listing ready, and the message sent from a listing
carries a small card of it (`threads_messages.post_id`). `threads.post_id` is the listing they last talked
about (the pinned card and the inbox line). The pair is `threads.user_low_id` + `user_high_id`, unique; the
old unique (post_id, buyer_id) is dropped. Migration `2026_11_23_000400` merges existing conversations of the
same two people into the oldest. Where the plan above says "one conversation per (listing, buyer)", read this.

Steps 0 to 3 are built and tested on local. What differs from the plan above, and why:

- **Photos open through a signed-in route, not a signed link.** `/chat/files/{message}` checks the session and
  that the member is in the conversation on every request. A signed link would work for anyone it was
  forwarded to until it expired; this does not.
- **A third hole was found and closed in step 0:** a reply could add any member to a conversation through a
  `recipients` field.
- **Types are text and photo.** `offer` and `system` wait for step 4; the column is a short string, so adding
  them needs no migration.
- **The digest marks a conversation once it is mentioned**, with a new `digest_sent_at` column on
  `threads_participants`.
- **The menu entry** "Messenger" in the header account menu is renamed "Chats" and points to `/chat` by a
  migration; the unread badge is filled in on the server and kept live.
- **Reports** are saved and emailed to the site's contact address with the last six messages. An admin page for
  them is still step 4.
- **Listings posted without an account** (guest posting) have no "Chat with seller": there is no member to talk
  to. The phone and WhatsApp buttons stay.
- **Scripts:** the page's code is six small files (`chat-kit`, `chat-inbox`, `chat-log`, `chat-safety`,
  `chat-photo`, `chat`), none over 800 lines, plus `chat-live` on every page for signed-in members.
- **Going live with sockets** is three steps on the server, written down in `docs/operations.md` ("Live chat")
  with the files in `deploy/reverb/`. Until they are done the chat works with the 10-second check.

Design: `replica/design/` (tokens, 27 colour pairs with 0 failing AA, and the component specs).

