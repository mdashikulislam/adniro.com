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
