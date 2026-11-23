# Chat audit (2026-11-23)

What is missing or weak in the live chat on branch `chat`, and what a professional marketplace would add.

**How it was done.** Three passes: (1) an independent reviewer read all of the chat's code, texts, tests and
server notes with no knowledge of how it was built; (2) a research pass on the chats of OLX, Facebook
Marketplace, Vinted, Carousell, Wallapop, Kleinanzeigen, Gumtree, Mercari, Craigslist and WhatsApp Business;
(3) checks on the running local site and read-only facts from the live database. Every item in section A was
confirmed in the code.

**Limits.** The research found official sources for only part of what those sites do (offers, block and report,
scam guidance, "delete for me", seller auto-replies). Read receipts, typing, voice, reactions, "last seen" and
most inbox tools could not be confirmed from official pages, so they are not called "standard" below.
Nothing here was tested on a real phone.

Sizes: **S** under an hour · **M** a few hours · **L** a day or more. Everything listed is free to build and run.

## A. Fix before going live

These are bugs or risks, not extras.

**Status (2026-11-23): A1 to A9 are fixed on local, on branch `chat`, each with tests.** A10 is not: it changes
the live server and waits for approval. One more case of A3 was found while testing by hand and fixed with it:
the new email form also lost everything after a "<".

| # | problem | what happens | fix | size |
| --- | --- | --- | --- | --- |
| A1 | **Deleting a listing erases the whole conversation** | `PostObserver` deletes every conversation whose current listing is the one deleted, with all its messages, photos and reports. Since two people now share one conversation, a seller deleting one sold item wipes everything the two ever said. A scammer can erase the evidence, and the report against them, by deleting the listing. | Never delete conversations with a listing; just detach the listing (the chat already shows "No longer available"). | M |
| A2 | **12% of listings cannot be contacted by chat** | On live, 518 of 4,332 active listings were posted without an account, so they have no "Chat with seller". 169 of those have no phone number either: once the old contact form is gone, nobody can reach them at all. | For listings without an account, keep a simple "Email the seller" form (rate-limited, sent by the site, the seller's address never shown). | M |
| A3 | **Text after "<" disappears** | "Can you do <500 for both?" is saved as "Can you do". "I <3 this bike" becomes "I". The sender is not told. | Stop stripping tags on save; the chat already shows text safely as plain text. | S |
| A4 | **Deleting an account breaks the other person's chats** | The member's messages are removed but the other person keeps a stale unread count that can never clear, and a pointer to a message that no longer exists. This hits every victim when an admin deletes a spammer. | Recount the affected conversations when an account is deleted. | M |
| A5 | **The unread email has no off switch and uses the wrong language** | No unsubscribe link or preference; it is sent in the server's language, not the member's. On the first run after go-live it would also email about old unread Messenger messages (1 member on live today). | Add a link and a setting to turn it off, send in the member's language, and mark old messages as already notified. | S–M |
| A6 | **Blocking does not hide "seen"** | A blocked person still sees their messages turn "seen" when the blocker opens the chat. Every other path (sending, starting from another listing, online status, the email) is closed. | Stop sending read state across a block. | S |
| A7 | **Messages can appear out of order after a reconnect, and catch-up stops at 100** | A live message that lands before the catch-up answer is drawn above older ones. After a long break only the first 100 missed messages load. | Insert by message number; keep loading until caught up. | S |
| A8 | **Server notes are wrong in two places** | My notes say to run `config:cache` for the chat; this site must not cache config (and live does not). The deploy script does not restart the socket server, and the health check does not watch it. | Correct the notes, add the restart to the script, add the socket server to `/health`. | S |
| A9 | **Conversations a member deleted in the old Messenger come back** | The upgrade un-hides them. On live nobody has deleted one, so nobody is affected today. | Keep them hidden for that member. | S |
| A10 | **One old attachment is still reachable by its direct link** (already live, known) | Needs the exact, random file name. | One nginx rule; waits for your approval because it changes the server. | S |

## B. What a professional marketplace has and this chat does not

Ordered by how much users would notice.

**Status (2026-11-23): round 2 is built on local, on branch `chat`, with tests: B1, B3, B4, B5, B6, B8 and B14.**
Two limits, on purpose: the quick replies stay in the language files (an admin text box would lose their
translations), and the warning before sending (B5) still knows English "pay outside" words only (section C).

**Round 3 is built on local as well, with tests: B2 (sound and browser notifications), B7 (offers) and B10
(seller tools).** Three limits, on purpose:
- **B2 has no phone push.** A sound and a browser notification need the site open in a tab. A notification on a
  phone whose browser is closed needs a service worker and push subscriptions, which went out with the phone
  app; it stays a later item. Android browsers are not offered the notification switch: a page cannot show one
  there.
- **An accepted offer changes nothing on the listing.** No price change, no reservation, no payment: it is an
  agreement between two people, shown to both.
- **"Mark as sold" takes the listing offline**, the way My listings does: the site has no separate "sold" state.

**Round 4 is built on local as well, with tests: B9 (find and tidy the inbox), B11 (privacy choices), B12
(photos), B13 (screen readers and keyboard) and B15 (mixed languages).** Limits, on purpose:
- **The search looks at names and at the listing last talked about**, not inside messages.
- **A new message brings an archived chat back.** An archive that swallowed new messages would hide buyers.
- **Both privacy choices work both ways**: a member who hides "online" or "seen" does not see it of others.
- **Several photos are several messages**, sent in order; there is no album bubble.
- **Screen readers were checked by reading the page's structure, not with a screen reader.** Section C's
  "tests in a real browser" would be the place to add one.

| # | missing | why it matters | seen on | size |
| --- | --- | --- | --- | --- |
| B1 | **Delete a conversation (for me) and delete my own message** | A message sent to the wrong person stays for good; an inbox can never be cleaned. | Vinted and Kleinanzeigen: delete for yourself only (verified) | M |
| B2 | **Alerts when the site is not on screen** | A seller's only alert is an email after 10 minutes. No sound, no browser or phone notification; a tab hidden for 5 minutes even stops updating its title. | Mercari message centre (verified); push is the norm in apps | M–L |
| B3 | **Admin tools for reports** | The report email shows member numbers, not names, six cut-off messages and no links. Admins cannot open the conversation, there is no list of reports, and a report can never be closed. | Gumtree states reports are reviewed within four hours (verified) | M |
| B4 | **Admin switch and settings** | Chat, photos, send limits, quick replies and the email delay are fixed in code. The chat cannot be turned off without a developer. | — | M |
| B5 | **A warning before sending a phone number, email or "pay outside" wording** | Today the caution appears only for the reader, after sending, and only knows English words. | Kleinanzeigen warns before sending; OLX shows a tip (verified, older sources) | S–M |
| B6 | **"Adniro will never ask for codes or payments in chat"** | The commonest scam is a fake "support" message. | Mercari, Wallapop, Gumtree, Vinted (verified) | S |
| B7 | **Make an offer** | Price talk is the main use of a marketplace chat. | Vinted, Carousell, Mercari (verified) | M |
| B8 | **Drafts are lost** | Switching chats, a reload or an expired session throws away what was typed. | — | S |
| B9 | **Find and tidy the inbox** | Only All and Unread. No search by name or listing, no archive, no mute, no "mark unread". | not confirmed by research | M |
| B10 | **Seller tools** | Quick replies exist only for the buyer. No saved answers, no away message, no "mark as sold" from the chat. | WhatsApp Business quick replies and away message; Facebook and Kleinanzeigen AI reply suggestions (verified) | M |
| B11 | **Privacy choices** | Online status and "seen" cannot be turned off, and there is no page listing the people you blocked. | not confirmed by research | S–M |
| B12 | **Photos** | One at a time, no paste or drag-and-drop, and a tap opens a new tab instead of a viewer. | — | M |
| B13 | **Screen readers and keyboard** | Opening a chat does not move focus; the inbox and the messages are re-announced on every update. | — | M |
| B14 | **"New messages" line** | Opening a chat with unread messages does not show where they start. | — | S |
| B15 | **Mixed languages** | An Arabic message on an English page (or the reverse) aligns badly. Only English is active on live today. | — | S |

## C. Later

- **Trust signals from chat:** "usually replies within…", "last active". Not confirmed as standard; also, the site
  does not record members' last activity on live today.
- **Clean-up:** messages and photos are never pruned; the email job reads the whole participants table each minute.
- **Scam caution:** English keywords only; any 9-digit number counts as a phone number.
- **Texts:** eight other site languages have no chat texts and fall back to English; the daily limit shows
  "wait a moment".
- **Tests in a real browser** (Playwright) for the main flows; today the screens were checked by hand.
- **Image checks** on chat photos, message translation, a "you are leaving Adniro" page for links,
  reactions and voice messages: not confirmed as standard on the sites researched.

## What is already at a professional level

One conversation per person with the listing shown on the message · instant delivery with a fallback ·
typing, online and seen · replies with quotes · quick replies · block and report · private photos ·
send limits, tighter for new accounts · unread badge on every page, a pop-up and the tab title ·
one email instead of one per message · two independent security and correctness reviews, 490 tests.

## Suggested order

1. **Round 1: section A** (about a day). Nothing in it is optional before real users rely on the chat.
2. **Round 2: B1, B3, B4, B5, B6, B8, B14** (deleting, admin tools and settings, safer sending, drafts).
3. **Round 3: B2** (sound and browser notifications first, phone push after), **B7** (offers), **B10** (seller tools).
4. **Round 4:** B9, B11, B12, B13, B15, then section C.
