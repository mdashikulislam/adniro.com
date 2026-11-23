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
  desktop ≥992   one panel in the page container: inbox 360px | conversation fills the rest
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

Inbox row (C2)
  one row per person, whatever the number of listings talked about
  parts     avatar 44 (photo or initials) + online dot · name (title) · the listing last talked about (sm) ·
            last message (sm, one line, "You: " prefix for my own, "Photo" for a photo) · time (xs) ·
            unread count (pill, accent-strong)
  states    default, hover (surface), active conversation (accent-soft + 3px accent bar at the start edge),
            unread (name and last message bold), blocked (muted, "Blocked" tag)
  a11y      one link per row; the unread count has a spoken label ("3 unread messages")

Inbox filter (C2)
  variants  All, Unread (segmented pills, the count on Unread)
  states    selected: bg accent-soft, text accent-strong, 700

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
  parts     photo 44 (radius sm) · label (xs/600, muted): "Last enquiry", or "Asking about" before the first
            message about this listing · title (sm/700, one line) · price (sm, accent-strong) · chevron
  states    default, hover (surface), listing no longer available (muted, "No longer available", not a link)
  tokens    bg bg, border-bottom border, 12px padding
  a11y      one link, "View listing: {title}"

Safety note (C3, C4)
  content   shield icon, the existing safety line and "Safety tips" link, shown once at the top of the log
  tokens    bg warning-bg, text warning-text, radius md, sm

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

Unread badge (G)
  where     the header account menu's Chats entry, and the Chats tab of the phone app bar
  tokens    pill, bg danger (header, as today) / accent-strong (app bar), text on-accent, xs/700, tabular numbers
  states    hidden at 0, "9+" from 10; updates live
  a11y      "Chats, 3 unread" on the link

Chats tab (G, phone app bar)
  content   chat-dots icon (filled when active), "Chats"; takes Saved's place; Saved moves to the account menu
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
