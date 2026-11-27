# Adniro live chat: components

Design for the screens in `replica/architecture.md`. Bootstrap 5.3 and the site's own teal; tokens in
`tokens.json` (27 colour pairs, 0 failing AA) and `tokens.css`. No Tailwind on this site: the tokens are CSS
custom properties (`--adn-chat-*`) at the top of `public/assets/css/chat.css`, with dark values under
`[data-bs-theme="dark"]`. Icons are Bootstrap Icons, already on the site. All copy is in `lang/*/chat.php`.

One rule that came out of the contrast check: **white text never sits on the site teal `#0d9488`** at body size
(3.74:1). Bubbles, the send button and teal text use the deeper `accent-strong #0f766e` (5.47:1). The site teal
stays for icons, rings, dots and large shapes (3:1 is enough there).

Screens: **C1** listing page · **C2** inbox · **C3** conversation · **C4** new conversation (no message yet) ·
**C5** report dialog · **G** every page (header, app bar).

## Layout

```
Chat shell (C2, C3, C4)
  desktop ≥992   one panel in the page container: inbox 400px | conversation fills the rest
                 height: the screen minus the header, never taller than 820px; radius panel, shadow panel
  phones <992    C2 is the inbox as a normal page (header and app bar stay)
                 C3/C4 are full screen: position fixed, 100dvh, the site header, footer and app bar hidden,
                 the composer sits above the keyboard and the safe area
  a11y           landmarks: inbox <nav aria-label="Chats">, conversation <main>; the message list is
                 role="log" aria-live="polite", so new messages are read out
```

## Components

```
Chat with seller button (C1)
  variants  listing actions row (ghost, with icon), seller card (solid, full width)
  states    default, hover, focus-visible; guest → opens the sign-in pop-up; owner → not shown
  tokens    solid: bg accent-strong, text on-accent, radius pill, 44px high, sm/700
  a11y      a real link to /chat/new/{listing}; the label says "Chat with seller"

Chat button on a listing card (C1)
  where     on the card's photo, under the save button; not on my own listing, nor where there is nobody to
            chat with (a listing posted without a member account)
  look      a 34px round button, teal gradient (#14b8a6 to #0f766e), white chat icon, soft teal shadow; where
            there is a mouse it opens into a pill with the word "Chat"; pressed scale .96
  action    goes to the listing's page, which opens the chat pop-up (the address ends in #chat); a guest gets
            the sign-in pop-up there
  a11y      a real link named "Chat with seller"; white focus ring

Chat pop-up on the listing's page (C1)
  opens     only from a listing card's chat button; the page's own "Chat with seller" buttons go to the chat,
            as before
  head      deep teal gradient with a lighter glow in one corner: the seller's photo or initial in a white
            ring, "CHAT WITH SELLER" (xs caps), the seller's name (18/800), a round close button; the white
            body rises over it with a rounded edge
  body      the listing (photo 52, "About this listing", title, price) · the four ready questions as
            accent-soft pills (a tap puts the words in the box) · the message box (surface, radius 16, teal
            ring on focus) · "Open full chat" link and the "Send message" pill (deep teal gradient)
  foot      amber strip with a lock: "Adniro will never ask you for a code, a password or a payment in a chat."
  states    empty (Send disabled), sending (the plane moves), error under the box (too fast, blocked, chat
            off), sent: a teal check in a soft ring, "Message sent", who gets it, "Keep browsing" and
            "Open chat"; closing it brings the message box back for next time
  keys      Enter sends on a computer, Shift + Enter makes a new line; the box has the focus when it opens
            (not on a phone, where the keyboard waits for a tap)
  a11y      Bootstrap modal named by its title; errors are role="alert"; text keeps its own direction

Email the seller button and dialog (C1)
  when      the listing was posted without a member account: there is nobody to chat with
  button    takes the place of "Chat with seller", same shape, envelope icon; a guest gets the sign-in pop-up
  dialog    Bootstrap modal: one line saying the seller is not on chat, a message box (10 to 1000 characters),
            a note that the member's name and email address go with it, Cancel + "Send email" (primary)
  states    too short (said under the box, before anything is sent), sending (button disabled), sent
            (the dialog closes, SweetAlert2 toast), error under the box
  a11y      the box is described by the note; errors are role="alert"

Inbox row (C2)
  one row per person, whatever the number of listings talked about
  parts     avatar 44 (photo or initials) + online dot · name (title) · the listing last talked about (sm) ·
            last message (sm, one line, "You: " prefix for my own, "Photo" for a photo) · time (xs) ·
            unread count (pill, accent-strong)
  states    default, hover (surface), active conversation (accent-soft + 3px accent bar at the start edge),
            unread (name and last message bold), blocked (muted, "Blocked" tag)
  verified  a teal check-badge icon right after the name, no text, when the member confirmed their email
            (the site's own rule, as on the listing page); also after the name in the conversation's head;
            spoken "Verified", hover says why
  a11y      one link per row; the unread count has a spoken label ("3 unread messages")

Inbox filter (C2)
  variants  All, Unread (segmented pills, the count on Unread)
  states    selected: bg accent-soft, text accent-strong, 700

Chat settings (C2)
  where     a gear (40px, muted) at the end of the inbox header, after the filters
  content   a small menu with one switch: "Email me about unread messages" (sm/700) and one line of help (xs,
            muted); the switch is accent-strong when on
  action    saved as soon as the switch moves, with a toast ("…are on" / "…are off"); it goes back if saving fails
  a11y      the gear is labelled "Chat settings"; the switch is a real checkbox with role="switch" inside its label

Inbox empty (C2)
  content   chat icon in an accent-soft circle, "No chats yet", one line of help, "Browse listings" button
  also      the conversation side on desktop with nothing open: "Pick a chat to start"

Conversation header (C3, C4)
  parts     back arrow (phones only, 44px) · avatar 36 + online dot · name (title) ·
            status line (xs): "Online" (success dot) / "Typing…" (accent-strong) / nothing ·
            menu (⋮, 44px): View listing, Block or Unblock, Report
  height    60px, border-bottom border
  a11y      the menu is a Bootstrap dropdown (keyboard: arrows, Esc); status changes are aria-live="polite"

Pinned listing card (C3, C4)
  parts     photo 44 (radius sm) · title (sm/700, one line) · price (sm, accent-strong) · chevron; a label
            (xs/600, muted) only when it is not yet the listing talked about: "Asking about", before the
            first message about it (the "Last enquiry" label was removed on the owner's request)
  states    default, hover (surface), listing no longer available (muted, "No longer available", not a link)
  tokens    bg bg, border-bottom border, 12px padding
  a11y      one link, "View listing: {title}"

Safety card (C3, C4)
  where     once at the top of the log, as wide as the messages; a white card (radius 16, soft teal shadow)
            with a 3px line across its top running from the site's teal into amber
  head      a teal gradient shield badge (40px, radius 13, white icon) · "Stay safe" (16/800) · at the end a
            "Safety tips →" pill (accent-soft, accent-text), shown when the site has a safety page
  tips      three tiles side by side (surface, radius 12), each a round icon and one habit: meet in a public
            place · check the item before you pay · never send a deposit or share codes; a short list on phones
  foot      an amber strip with a lock: "Adniro will never ask you for a code, a password or a payment in a
            chat." (warning-bg, warning-text, 700)
  a11y      role="note" with the title as its name; icons are decoration

Listing reference (C3)
  when      a message was sent from a listing ("Chat with seller"): two people have one conversation, so this
            says which listing the message is about
  content   above the bubble, on the sender's side: photo 40 (radius sm) · title (sm/700, one line) ·
            price (sm, accent-strong); a link to the listing, or plain and muted when it is gone
  tokens    bg bg, border border, radius md; starts a new group (12px above)
  a11y      a link named by the listing's title

Scam hint (C3)
  when      a message from the other person has a link, an email, a phone number or "pay outside" wording
  content   under that bubble: "Be careful: don't pay or share codes outside Adniro." (xs, warning-text)

Day separator
  content   "Today", "Yesterday" or the date, centred pill: bg surface, text text-muted, xs/600

Message bubble
  variants  mine (bg accent-strong, text on-accent, at the end edge) · theirs (bg surface, text text) ·
            photo (the image fills the bubble, radius bubble, max 260px wide, the time on a dark scrim) ·
            system (centred, muted, no bubble)
  shape     radius bubble (18); the corner next to the sender is 6; a run of bubbles from one person is
            4px apart, a change of person 12px; max width 78% (phones), 62% (desktop)
  meta      time (xs) inside the bubble at the end; mine also carry the tick
  states    sending (70% opacity, clock icon), sent (one tick, on-accent-muted), seen (two ticks, on-accent),
            failed (bg stays, a red line under it: "Not sent. Tap to try again", role="alert")
  text      15/21, wraps anywhere (overflow-wrap), links are underlined and open in a new tab
  a11y      each bubble: "You, 10:42, seen: text" / "Sam, 10:41: text" as a hidden label; ticks have text

Reply to a message (C3)
  button    beside every saved bubble (outside it, on the inner side): reply icon, 32px with a 40px hit area;
            appears on hover or focus, always faintly on touch screens; hidden when the chat cannot be written in
  bar       above the message box while replying: 3px accent edge, the person's name (accent-strong, sm/700),
            one line of the message ("Photo" for a photo), a close button (40px); Esc-free: the button cancels
  quote     at the top of the reply's bubble: the name and up to two lines of the message, on a tinted block with
            a 3px edge; a button: a tap scrolls the message area to the original and rings it for 1.4s
            (a steady ring, no animation, under reduced motion)
  rules     only a message of the same conversation can be quoted; a deleted original leaves a plain message
  a11y      "Reply" on the button; the bar is labelled "Replying to {name}"; the quote is a real button

Deleted message (C3)
  when      its sender took it back (for a while after sending; the time is an admin setting)
  content   in the bubble's place, on the same side: a slash-circle icon and "Message deleted", italic, muted,
            a 1px outline and no fill, mine and theirs alike; the time stays, the tick does not
  rules     no Reply or Delete button; a quote of it reads "Message deleted"; the inbox line too

Delete button (C3)
  where     beside my own bubbles, next to Reply, while the message can still be taken back
  content   trash icon, the Reply button's shape and behaviour (32px, 40px hit area); red when pointed at
  action    SweetAlert2 confirm: "Delete this message?" + "It is removed for both of you…"
  a11y      "Delete message" on the button

New messages line (C3)
  when      a chat is opened with unread messages
  content   a hairline in the accent colour across the log with a pill "New messages" (accent-soft, accent
            text), before the first unread message; the log opens there, not at the end

Question before a risky message (C3)
  when      the text has a phone number, an email address, or "pay outside" wording; once per chat and kind
  content   SweetAlert2 warning: a title ("Share your contact details?" / "Careful with payments"), two lines
            of why, "Edit message" (focused) and "Send anyway"

Delete chat (C3)
  where     the conversation's menu (⋮), above Report
  action    SweetAlert2 confirm: "Delete the chat with {name}?" and what it does; then the inbox, with a toast

Chat paused (C3, C4)
  when      an admin turned the chat off
  content   the composer's place takes one line: "Chat is paused for the moment. You can still read your
            messages."

Typing indicator
  content   a "theirs" bubble with three dots that rise in turn (1.2s); still under reduced motion
  a11y      hidden from the log; the header status says "Typing…"

New messages button
  when      a message arrives while the reader has scrolled up
  content   round pill above the composer: arrow down + "New messages"; tap scrolls to the end

Quick replies (C4, and C3 until the buyer has written)
  content   chips in one scrolling row: "Is this still available?", "What's your best price?",
            "Where can we meet?", "Can you send more photos?"
  states    default (bg bg, border accent, text accent-strong), hover/active (bg accent-soft), focus-visible
  action    one tap sends it
  a11y      a group labelled "Quick replies"; chips are buttons, 40px high

Composer (C3, C4)
  parts     photo button (image icon, 44px, ghost) · text box (grows from 1 to 5 lines, radius 22,
            bg surface, 16px text so iPhones don't zoom) · send button (44px circle, bg accent-strong)
  states    send: disabled when empty (surface, muted icon), ready, sending (spinner);
            text box: focus (2px accent ring); blocked: the whole composer is replaced by one line,
            "You blocked this person. Unblock" / "You can't reply to this chat."
  limits    2000 characters; the counter appears from 1800
  keys      Enter sends, Shift+Enter makes a new line (desktop); on phones Enter makes a new line
  hint      "Shift + Enter for a new line" (xs, muted, the two keys as small key caps) under the text box,
            on computers only and only while the box has the cursor; it sits in reserved space, so nothing
            moves; the text box is described by it for screen readers
  a11y      the text box has a label ("Message"); buttons have labels ("Send", "Add a photo")

Photo preview (C3, C4)
  when      a photo is picked, before sending
  content   thumbnail 64 above the composer with a remove (×) button; errors under it
            ("Photos only: JPG, PNG, WebP or HEIC", "This photo is too large (8 MB max)")

Connection bar (C3, C4)
  when      the live connection is down
  content   slim bar under the header: "Reconnecting…" (bg warning-bg, text warning-text, xs/600);
            the chat keeps working by checking every few seconds

Report dialog (C5)
  content   Bootstrap modal: "Report this chat", four reasons as radio rows (Spam, Scam or fraud,
            Abuse or harassment, Something else), an optional note (500), Cancel + Send report (danger)
  states    sending, sent (SweetAlert2 toast: "Thanks. We'll take a look."), error
  a11y      focus goes to the first reason; Esc closes

Block confirm
  content   SweetAlert2 confirm (the site's one dialog style): "Block {name}?" + what it does

Offer card (round 3)
  where     in the message log, under the listing it is about; the same card for both people (never a teal bubble)
  content   label "OFFER" / "YOUR OFFER" (xs/700, caps, accent-text) with a tag icon, the amount (24/800,
            tabular numbers), then one of: Decline + Accept (the person it was made to), "Waiting for an answer"
            (its sender), "Accepted" (success), "Declined", "Replaced by a newer offer"
  tokens    bg, 1.5px accent outline, soft shadow; closed offers: 1px border, amount struck through and muted
  buttons   pills 40px high; Accept is accent-strong with white text; pressed scale .96; never break a word
  states    open, accepted, declined, replaced, taken back ("Message deleted"); buttons disabled while saving
  a11y      real buttons; Accept asks first (SweetAlert2): "Accept {amount}?" and what it means

Offer answer (round 3)
  content   one line with an icon: "Offer accepted: {amount}" (success, accent-soft bubble) and the note
            "Agree where to meet. Pay only when you have seen the item."; or "Offer declined: {amount}" (plain)

Make an offer dialog (round 3)
  content   SweetAlert2: "Make an offer", the listing's title and price, one field "Your price ({currency})"
            (decimal keyboard), Cancel + Send offer
  rules     "1 200,50", "1.200,50" and "1,200.50" are read as the same amount; anything else shows
            "Enter an amount, for example 120 or 120.50."

"+" menu beside the message box (round 3)
  content   dropup: "Make an offer" (when the listing allows it), "Saved replies" header, the member's saved
            replies (two lines each at most), "Edit saved replies"
  action    a saved reply goes into the message box, to be changed or sent

Saved replies and away message dialog (round 3)
  content   Bootstrap modal: away message (switch + text, 300) with what it does; saved replies (up to five,
            300 each, add and remove); Cancel + Save
  states    saving, saved (toast), error under the fields ("Write your away message, or turn it off.")

Away reminder (round 3)
  content   pill above the list of chats while the away message is on: moon icon, "Away message on"
            (warning-bg, warning-text); opens the dialog

Automatic reply mark (round 3)
  content   small "Automatic reply" line with a robot icon at the top of the bubble of an away message

Chat settings (round 3 additions)
  content   under the email switch: "Sound for new messages" and "Browser notifications" (kept in the browser),
            then the link "Saved replies and away message"
  rules     the notifications switch asks the browser the first time; refused: a toast says where to allow it;
            not shown where a page cannot show notifications (Android)

Chat icon in the header (G)
  where     signed-in members only: in the menu bar from 1200px, beside the account button from 768px;
            phones use the app bar's Chats tab
  content   chat-dots icon in a 42px rounded button (as the header's other buttons), the unread count on its
            corner: white on red (5.46:1), a 2px ring in the page colour, hidden at 0, "99+" above 99
  states    default, hover and focus-visible (primary tint), pressed (scale .96)

New-message pop-up (G)
  when      a message arrives while the member is on any page but the chat page; never for their own messages
  content   top corner under the header (the app bar and the cookie banner are at the bottom): avatar 40,
            the sender's name (sm/700), up to two lines of the message ("Photo" for a photo), a close button (40px)
  action    the whole card opens that conversation
  rules     at most three at once; each leaves after 7s (the countdown waits while it is hovered, and while
            the tab is not being looked at); text is inserted as text, never as HTML
  a11y      role="status", aria-live="polite"; the close button is labelled "Dismiss"

Browser tab (G)
  content   "(2) " before the page title while there are unread messages

Unread badge (G)
  where     the header's chat icon, the account menu's Chats entry, and the Chats tab of the phone app bar
  tokens    pill, bg danger (header, as today) / accent-strong (app bar), text on-accent, xs/700, tabular numbers
  states    hidden at 0, "9+" from 10; updates live
  a11y      "Chats, 3 unread" on the link

Chats tab (G, phone app bar)
  content   chat-dots icon (filled when active), "Chats"; takes Saved's place; Saved moves to the account menu

Inbox search (round 4)
  where     under the title, above the filters; full width, pill, 40px, surface, a magnifier at the start
  states    empty (placeholder "Search by name or listing"), typing (a clear button at the end), focus (bg +
            2px accent ring), searching (three skeleton rows), nothing found ("No chats found." + a hint)
  rules     starts at two characters, a short moment after the last key; looks in every chat, archived or not,
            so no filter is on while it runs; a filter, the clear button or Escape ends it
  a11y      role="search", a spoken label; the number found is said once ("Chats found: 3")

Inbox filter (round 4)
  variants  All, Unread (with the count), Archived: three equal pills on their own row
  empty     Archived: "No archived chats."

Row menu (round 4)
  where     a round "…" button at the end of each row: on a computer it appears on hover or focus, over the
            time, which steps aside; on a touch screen it is always there, quieter, with room kept for it
  content   Mark as unread / Mark as read · Mute / Unmute · Archive / Move back to chats (each says what it
            would do now); the open conversation's own menu has the same three, above Block
  marks     a muted chat shows a small crossed bell beside its time; a chat found by a search outside the
            list shows an "Archived" tag
  after     a toast says what happened ("Chat archived. A new message brings it back."); the keyboard goes
            back to the row's button; marking the open chat unread closes it
  a11y      the button names the person ("More actions for the chat with Sam"); the list is not redrawn while
            a menu is open

Privacy and blocked people dialog (round 4)
  where     the last link of the chat settings (shield icon)
  content   Bootstrap modal: two switches, each with what it does and that it works both ways ("Show when I'm
            online", "Show when I've read messages"), then "People you blocked": avatar, name, Unblock
  states    a switch saves as it moves and goes back if saving fails; the list: loading, empty ("You haven't
            blocked anyone."), error
  a11y      each Unblock button names the person

Photos to send (round 4)
  where     a row of 64px thumbnails above the message box, each with a round remove button at its corner
  from      the photo button (several at once), a paste into the message box, a drop on the conversation
  rules     up to six at a time; each is shrunk before it is shown; one that cannot be read is skipped with a
            word why; Send sends them in order, then the text
  drop      while photos are dragged over the conversation: a dashed accent frame and a pill "Drop photos
            here to send them"
  a11y      the row is a list with a spoken label; each remove button says which photo it removes

Photo viewer (round 4)
  what      a tap on a photo opens it on the page, full screen, on a near-black ground
  bar       who sent it and when · "2 of 5" · open in a new tab · close (44px round buttons)
  moves     previous and next buttons at the sides, the arrow keys, a swipe; they stop at the first and the
            last photo of the conversation
  loading   the small photo shows at once, the full one takes its place (a small spinner meanwhile)
  closes    the close button, Escape, a tap beside the photo; the keyboard returns to the photo it came from
  a11y      a <dialog>: the browser keeps the focus inside; the photo's text says who sent it

Mixed directions (round 4)
  message   the text reads and lines up in the direction of what is written, line by line; the time and the
            tick stay in the same corner of every bubble
  one line  a name, a title or a preview that is cut short is cut at its end in its own direction, and stays
            on the side the page's language puts it
  typing    the message box follows what is typed; empty, it follows the page

For screen readers (round 4)
  said      one hidden polite status line: the message that arrives in the open chat ("Sam: …"), "New message
            from Sam" for another chat (not a muted one), the number of chats a search found
  not said  the list of chats and the messages are not live regions: they are redrawn too often
  focus     opening a chat: the message box on a computer, the person's name (a heading) on a phone or when
            nothing can be written; Back: the chat's row; the list keeps the keyboard's place when redrawn
  keys      up and down arrows move through the list of chats; Escape gives up a reply
```

## Listing form: selling tips card

```
Selling tips (form/panel.blade.php, listing-form-panel.css ".lf-tips")
  where     the listing form's side panel, details step only; in the bottom sheet on phones
  shape     a panel card (radius 16, 1px border) that is always open: no <details>, nothing to unfold
  head      a 36px teal tile (radius 12, primary to a darker primary, white filled lightbulb, soft teal
            shadow) and the heading, on a teal wash: 5% tint plus a 16% glow from the top end corner,
            closed by a 1px 14% teal line
  list      an ordered list of five tips; each is a 32px icon tile (radius 10, 10% teal tint, dark teal
            icon) and the tip in 14px text, first line level with the middle of the icon
  rail      a 2px, 20% teal line from under one icon to just above the next (3px clear of each)
  icons     one per tip, by what it is about: card-text, grid, camera, tag, check2-circle (Bootstrap Icons)
  states    static: no hover, no focus stop, nothing moves
  dark      icons 55% teal + white on an 18% tint; wash 7% + 20%; rail 30%
  tokens    text on bg; accent-strong on accent-soft (icons, 4.91:1); on-accent on accent-strong (head tile,
            5.47:1); the dark pairs of the same roles. All already in tokens.json: 27 pairs, 0 failing AA.
  a11y      <section aria-labelledby> with an <h2>; a real list (role="list", since list-style is none);
            every icon aria-hidden; the tip's text is the only thing read
  copy      the site's own five tips (global.sell_quickly_advice_1..5), unchanged
```

## Sitemap page

Built from what the homepage already uses, measured on the page itself (2026-11-27): Roboto 14.4px for text,
Roboto Condensed 700 for titles, the night-scene hero with a white uppercase title and a white pill search
bar (999px, 6px padding, teal pill button), section titles with a 4px teal bar and one part in bold, category
icons in 9% teal circles that fill with teal on hover, 14 to 16px card corners on a 1px #dee2e6 border.

```
Head (front/sitemap/index.blade.php ".sm-hero")
  look      a 24px-cornered panel: deep blue-green night (#06202b to #0b3f47) with two teal glows, a faint
            street grid that fades to the edges, three glowing map pins (none on phones)
  content   a glass pill with the site and the country, the title in white uppercase (as .hero-title), one
            light line of intro (as .hero-subtitle), the search bar, a line saying how many were found
  tokens    on-night on night 16.8:1 and on night-end 11.6:1; on-night-accent on night 11.4:1

Search (".sm-search", public/assets/js/app/sitemap.js)
  what      filters the page as one types; hidden until its script runs, so the page is whole without it
  parts     magnifier, field (52px, 46px on phones), a round clear button once there is text, the teal
            "Find" pill, which scrolls to what was found (icon only on phones, the word kept for readers)
  states    rest: white pill with a 1px white ring and a deep shadow; focus: 3px teal ring; typing: the
            cards go one column wide; found: "8 found" under the bar; nothing found: the empty block
  keys      Enter scrolls to the results, Escape clears
  a11y      role="search", a hidden label, the count in a polite live region, clear buttons named

Figures bar (".sm-stats")
  look      a white 22px-cornered bar over the head's lower edge, never wider than the head less 3rem
  cells     an icon circle, the number in Roboto Condensed 700, its name in small capitals; hairlines between
  states    a cell that leads to a part of the page is a link: hover tints it, fills its icon and nudges
            its arrow down; the sub-categories cell leads nowhere and is left out on phones

Category card (".sm-cat")
  width     as wide as its list is long, on a grid of 4, 3, 2 then 1 columns; every row is full and the page
            is as short as it can be (App\Helpers\Common\SitemapBento); its links run in as many columns
  head      the category's icon in a 48px circle, its name (a link), "7 sub-categories", a round arrow link
  list      every sub-category, 14.4px, its own icon before it in a 26px tinted tile (as the city tiles' pin)
  states    card hover or focus inside: teal border, a soft lift, the icon circle fills; link hover or focus:
            teal text on a 7% tint, the icon's tile fills, an arrow slides in; arrow link hover: fills and turns
  tokens    text on bg; text-muted on bg 6:1; accent-strong on bg 5.5:1

Cities (the homepage's "Choose a city" tiles: ".city-chip" in custom.css, nothing restyled)
  title     the homepage's icon tile (34px, 10px corners, 10% teal) and the title in bold, no teal bar
  grid      4 columns from 992px, 3 from 768px, 2 on phones, 8px apart; from A to Z
  tile      10px corners on a 1px border, a pin in a 26px tinted tile, the city's name on one line
  last      no "More cities" tile here: the page lists its cities itself
  states    as on the homepage: hover lifts the tile 2px, tints it and fills the pin's tile

Share bar (".sm-share", around the site's shared share links)
  look      a 22px-cornered bar on a 5% teal wash with a glow in the far corner and a teal hairline border
  parts     a teal gradient tile with a share icon, "Share this page", then one round 46px button a network
  button    white with a 1px border, the network's plain glyph in its own colour (no squares here)
  states    hover or focus: the button fills with its network's colour, darkened 28% so the white glyph
            stays clear on the light greens and blues, lifts 3px and gets a glow of that colour; pressed: .96
  dark      buttons on a 6% white surface; X is drawn in white and fills white with a black glyph
  phones    the buttons take their own line, spread across the bar
  a11y      a <section> with its heading; every link keeps its "Share on …" title; links from social-share.js

Nothing found (".sm-empty")
  look      a dashed teal frame, a search icon in a 72px circle, “Nothing matches “…””, a hint, and a teal
            "Clear search" pill that empties the field and puts the cursor back in it

Dark        cards, bar and pills take the page's dark surface and border; teal text and small icons turn
            #5eead4 (10:1 on the page); the head keeps its night colours
Motion      colour and shadow changes in 150 to 250ms, lifts of 1 to 2px; none with prefers-reduced-motion
```

## States every screen has

| screen | loading | empty | error |
| --- | --- | --- | --- |
| C2 inbox | three grey rows that shimmer | "No chats yet" | "Chats could not load. Try again" + button |
| C3 conversation | the log shows a small spinner at the top while older messages load | — | "Messages could not load. Try again"; a failed send stays in the log with "try again" |
| C4 new conversation | — | the listing card, the safety note and quick replies | if the listing is gone: "This listing is no longer available" |

## Motion

- A new bubble rises 6px and fades in over `base` (200ms); nothing else moves when a message arrives.
- The send button scales to 0.94 while pressed.
- Typing dots: 1.2s loop. Skeleton shimmer: 1.4s loop.
- `prefers-reduced-motion`: no rise, no loops, no smooth scroll.
- Only `opacity` and `transform` are animated; `transition: all` is never used.

## Not taken from anywhere

No logo, icon, illustration, sound or copy from another product. Icons are Bootstrap Icons (MIT); the
bubble shape, ticks and two-pane layout are common chat patterns, drawn here in Adniro's own colours.
