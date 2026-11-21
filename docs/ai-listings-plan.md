# AI for listings: moderation and quick post

> **Update 2026-11-21: the quick post composer was replaced.** At the owner's choice, the AI now lives in a box at the top of the normal listing form, ported from classifedz.com. It fills the open form in place, through `POST posts/create/assistant/fill` and the same draft service, limits and budget. The composer, its photo upload, one-screen review, form banner and `listing_assistant_one_screen` setting are gone, and `posts/create/quick` redirects to the form. Sections below that describe the composer are kept as history. See section 8 of docs/professional-website-plan.md.
>
> **Renamed to "AI listing assistant" (2026-11-21).** "Quick post" is gone from the code as well as the screens: `App\Services\ListingAssistant\ListingAssistantService`, settings `listing_assistant_*` (Settings > AI > AI listing assistant), config `ai_features.listing_assistant`, column `listing_assistant_eval_passed_at`, usage feature `listing_assistant`, routes `posts/create/assistant/fill` and `/api/listing-assistant/*`, and commands `ai:listing-assistant-eval` and `ai:listing-assistant-purge`. The migration `2026_11_21_000400_rename_quickpost_to_listing_assistant` renames what an older database stored. Names in the history sections below were updated by search and replace, so they show today's names.

Plan only. Nothing in this document has been implemented.
Prepared 2026-11-20 from two planner passes over the codebase, then checked against the code.
Revised 2026-11-20: the AI provider and model are managed by the admin, not fixed in code.
Revised 2026-11-20: the admin sets an AI post limit per day, week or month for members and guests.

## Implementation status

### Phase 1, shared foundation: built on branch `ai` (2026-11-20)
Committed as `224ad918`. Covered by 34 automated tests in `tests/Unit/Ai` and `tests/Feature/Ai`, run against a separate `adniro_test` database.

What exists:
- Admin > AI > AI providers and AI models, super admins only, with Test connection and Test model buttons.
- Admin > Settings > AI: master switch, a primary and fallback model per feature, daily budget and daily call limit.
- `App\Services\Ai\Contracts\AiClientContract`, implemented by `LaravelAiClient` on the Laravel AI SDK.
- `ai_providers`, `ai_models` and `ai_usage_logs` tables, and `php artisan ai:ping`.

Differences from the plan above:
- **Framework upgrade.** Laravel AI SDK 1.4 needs Laravel 12.62 or later. Laravel moved from 12.37 to 12.73 with 20 other packages, using Composer's minimal-changes mode. Known security advisories fell from 70 in 21 packages to 13 in 6. Key pages were compared before and after and returned identical responses.
- **Config file name.** The SDK owns the `ai` config key, so feature limits live in `config/ai_features.php`.
- **Fallback.** The client handles the fallback model itself. The SDK's own failover keys providers by name, which breaks when both models belong to one provider.
- **Keys at runtime.** Each call registers the provider with the SDK through `Ai::build()` from the database row, so a changed key or model applies on the next call without a worker restart.
- **Settings are read fresh.** The client reads Settings > AI from the database on every call, so no settings observer change was needed.
- **Costs.** All input tokens are priced at the input rate, including cached ones. That overstates spend slightly when caching works, which is the safe side for a budget.
- **Supported drivers.** Anthropic, OpenAI, Gemini, Mistral, Groq, xAI, DeepSeek, OpenRouter, Ollama and OpenAI-compatible. Azure and Bedrock need extra settings and are left out for now.

SDK facts confirmed in the spike:
- Refusals and safety blocks from every major provider arrive as one content-filter finish reason.
- An unreadable JSON answer comes back as an empty array, which the client treats as invalid.
- Token usage includes cache reads and writes. Cached tokens are counted inside the input total.
- The `CacheInstructions` attribute marks the instructions for caching on Anthropic.

Core files touched, each marked `adniro:ai`:
- `bootstrap/providers.php`
- `routes/web/admin.php`
- `resources/views/admin/layouts/partials/sidebar.blade.php`
- `phpunit.xml`, which now points tests at `adniro_test`
- `composer.json` and `composer.lock`

Known gap: the panel's generic inline toggle endpoint lets any admin switch the `active` flag of a table. The AI lists do not show that toggle, but the endpoint itself is not restricted.

### Phase 2, moderation data model and hiding: built on branch `ai` (2026-11-20)
Committed as `225567a7`.
No AI call is made yet, and nothing sets a status other than `approved` automatically. Covered by 11 more tests in `tests/Feature/Ai/ModerationVisibilityTest.php`, so 45 in all.

What exists:
- `posts.moderation_status` (default `approved`, indexed), `posts.moderation_hash` and `posts.moderated_at`. None of them is fillable.
- `post_moderations` table and `App\Models\PostModeration`, with `Post::moderations()`.
- `App\Services\Ai\Moderation\ModerationStatus` with the four values, and `ModerationVisibility::canSee()`, which allows the owner, staff and the admin panel.

Verified on the local site by setting one listing to `rejected` and back. The show page and its `?preview=1` link returned 410, the API show returned 404, and the listing left the API list, the home page and the listings sitemap. All came back after restoring `approved`.

Differences from the plan above:
- **Two more leaks closed.** The `?preview=1` show page and the API show endpoint with `unactivatedIncluded=1` load a listing without the visibility scopes for any visitor. Both now drop a hidden listing unless `ModerationVisibility::canSee()` allows it. The phone reveal uses the API path, so it is covered too.
- **`isVerifiedPost()` has a second argument.** Passing `false` checks email and phone only. The purge command uses it, so a listing waiting for moderation is never deleted as "never activated". The duplicate-title rule uses it, so its email and phone meaning is unchanged.
- **The `unverified` scope is now grouped.** With human review on, the old scope added a bare "or reviewed_at is null" that ignored other filters such as the owner. All its conditions now sit in one group.
- **The `reviewed` local scope** also requires `approved`. The feed, city counts and similar listings use it.
- **The settings page re-approval skips hidden listings.** Its bulk update runs through the global scopes, so a hidden listing keeps an empty `reviewed_at`. When human review is on, an admin approval in phase 5 must also set `reviewed_at`.

Core files touched, each marked `adniro:ai`:
- `app/Models/Scopes/ReviewedScope.php`
- `app/Models/Post.php`: the `verified`, `unverified` and `reviewed` scopes, a cast and the relation
- `app/Helpers/Services/Functions/admin.php`: `isVerifiedPost()`
- `app/Http/Resources/PostResource.php`
- `app/Services/Post/Show/DetailedTrait.php` and `app/Services/Post/ShowTrait.php`
- `app/Console/Commands/ListingsPurge.php` and `app/Rules/UniquenessOfPostRule.php`

Not done in this phase: owner banners and the "online" mail gate are phase 4. The admin queue is phase 5. The owner's view of a hidden listing was checked by tests, not in a signed-in browser.

### Phase 3, moderation in shadow mode: built on branch `ai` (2026-11-20)
Committed as `232a5435`.
Shadow mode only. Every check is recorded in `post_moderations`, and the listing itself is never changed. Moderation is off by default, so nothing runs until an admin picks shadow mode in Settings > AI > Moderation. Covered by 30 more tests in `tests/Unit/Ai/VerdictMapperTest.php` and `tests/Feature/Ai/ModerationShadowTest.php`, so 75 in all. No real provider was called, because no API key is set up.

What exists:
- **Policy and prompt.** `resources/ai/moderation-policy.md` holds the instructions, versioned by `Policy::VERSION`. `Policy` holds the 14 categories, their severity and the answer schema. Category names are translated in `lang/en/ai.php`.
- **Fingerprint.** `ContentHash` covers the title, description, tags, price, currency, category, seller type, contact name, custom field values and the sorted photo paths.
- **What the model sees.** `ListingPayload` sends plain text without HTML, category and city names, readable custom field values and up to five photos. Email and phone are never sent. The listing is JSON-encoded with `<` and `>` escaped, so it cannot close its tag.
- **Fixed rules.** `VerdictMapper` applies the five rules from section 5 and cleans the reason shown to users.
- **Scheduling.** Three observers, on listings, photos and custom field values, are registered from `AiServiceProvider`, so the app's own observers are unchanged. `ModerationService::schedule()` queues one `ModeratePost` job per listing, 15 seconds later, unique until it starts.
- **The check.** `ModerationService::moderate()` follows the job steps in section 5, including the re-check of the fingerprint after the answer and the per-listing and per-user daily limits.
- **Commands.** `ai:moderation-sweep` is scheduled every 5 minutes. `ai:moderation-eval {model?}` runs the 60 labelled listings in `resources/ai/eval/moderation.json` and sets or clears the model's `moderation_eval_passed_at`.
- **Settings > AI > Moderation.** Mode (off or shadow), checks per user per day (30) and checks per listing per day (10).

Differences from the plan above:
- **Extra columns.** `post_moderations` gained `mode`, `decided_status` and `rule`. `decided_status` records what the rules decided even in shadow mode, while `applied_status` stays empty until a decision is written to the listing.
- **Repeat checks in shadow mode.** Shadow mode leaves `posts.moderation_hash` alone, so an unchanged listing is recognised by an earlier AI row with the same fingerprint instead.
- **No worker locally.** When the AI queue connection uses the sync driver, the check runs after the response is sent instead of 15 seconds later. Locally `AI_QUEUE_CONNECTION` is not set, so checks go to the `ai` queue on Redis and wait for `php artisan queue:work redis --queue=ai`.
- **Not in the evaluation set: child safety.** Such content is never written into test data. The other 13 categories are covered, plus 5 attempts to instruct the moderator, in 7 languages.
- **Limits in shadow mode.** A listing over its limit gets a system row with `needs_review` and no AI call.

Core files touched, each marked `adniro:ai`:
- `app/Console/Kernel.php`: one schedule line for the sweep

Before phase 4:
- Add a provider and model, then run `php artisan ai:moderation-eval` on the model. It makes 60 real calls.
- Run shadow mode for about a week with a queue worker, and compare the `post_moderations` rows with your own judgement.
- Phase 4 should only offer the enforce mode for a model whose evaluation passed.

### Phase 4, enforce and notify: built on branch `ai` (2026-11-20)
Committed as `e7d45c1b`.
Covered by 13 more tests in `tests/Feature/Ai/ModerationEnforceTest.php`, so 88 in all. Moderation is still off locally, and no real provider was called.

What exists:
- **Enforce mode** in Settings > AI > Moderation. It only takes effect when every assigned moderation model has passed `ai:moderation-eval`. Until then, shadow mode stays in effect and the screen says so. Changing a model's settings clears its evaluation pass.
- **Pending in the same save.** In enforce mode, a new listing and a text edit are saved as `pending`, so the listing is never public before its check. A photo or custom field change marks the listing `pending` before the check is queued.
- **Decisions applied.** Approved and rejected listings get the content fingerprint and `moderated_at`. Errors, refusals and limits give `needs_review` without a fingerprint, so a later check can still run.
- **Earlier decisions reused.** Returning to content that was decided before restores that decision with no AI call. An admin's edit counts as accepted content.
- **"When the AI cannot be used"** is a new setting: publish (the default) or hold for an admin. It applies when the budget or call limit is reached, or no usable model is assigned.
- **The "online" email waits.** `OnlineEmailGate` holds the app's "your listing is online" email until the listing is approved. It sends it when the listing goes live, and skips it when a listing that was already live is re-approved after an edit.
- **Rejection email and SMS.** `PostNotApproved` gives the title, the category names, the cleaned reason, an Edit button, the fact that saving sends it for a new check, and a contact link. It is sent once per decision, in the language captured when the check was scheduled, to a verified email or by SMS for phone sign-ups. The AI's reason and the title are escaped.
- **Owner messages.** A banner on the listing page for pending, in review and rejected (with categories, reason and Edit button), shown to the owner and staff only. A status badge in My Listings. A line on both finish pages while enforce is on.
- **Sweep.** It now only looks at verified listings. When enforce is switched off, listings left waiting are published or held as the setting says.

Differences from the plan above:
- **Decision 4.** "AI down" is read as over budget or no usable model, which follows the new setting. A provider error after retries still goes to an admin, as decision 5 requires.
- **Strings.** Email, SMS, banner and badge texts live in `lang/en/ai.php`, so core language files are untouched. Other languages fall back to English until phase 9.
- **No admin alert yet.** Listings in review do not alert admins yet. That comes with the admin queue in phase 5.

Core files touched, each marked `adniro:ai`:
- `app/Observers/PostObserver.php`: the "online" email gate, about ten lines in `sendNotification()`
- `app/Http/Controllers/Web/Admin/AiModelController.php`: clears evaluation passes
- `resources/views/front/post/show/index.blade.php`: the owner banner
- `resources/views/front/account/posts.blade.php`: the status badge
- `resources/views/front/post/createOrEdit/multiSteps/create/finish.blade.php` and `.../singleStep/finish.blade.php`: the finish line

Not checked in a signed-in browser: the banner, badge and finish line were checked by rendering the views in tests.

### Phase 5, admin queue: built on branch `ai` (2026-11-20)
Committed as `edfe3a53`.
Covered by 14 more tests in `tests/Feature/Ai/ModerationQueueTest.php`, so 102 in all. The fake AI client used by the tests now has its own file, `tests/Feature/Ai/FakeAiClient.php`.

What exists:
- **Admin > Listings > AI review queue** at `/admin/moderation`. Its filters are needs review (the default), rejected, being checked, shadow flags and all hidden. It has a title or ID search, oldest first, 25 per page. Each row shows the status, the categories, the confidence, why it was decided, the model, the note for admins and the reason for the owner.
- **Shadow flags.** This lists live listings whose latest AI check would have held or rejected them, unless an admin approved them since. It is meant for the shadow week.
- **Approve** stores a human decision with the current fingerprint, publishes the listing and sends the "online" email when it was hidden. It also sets `reviewed_at` when human review is on. The same content is never sent to the AI again.
- **Reject** opens a form with the 14 categories and a message to the owner, prefilled from the AI. It needs at least one category. The message is cleaned like the AI's, and the owner gets the rejection email.
- **Re-run AI** queues a forced check that ignores earlier decisions and the per-listing limit. It is refused when moderation is off, or the listing is unverified or archived.
- **History** lists every decision on a listing: who decided (AI with its model, an admin by name, or the system), the decision, whether it was applied and emailed, and tokens and time.
- **Sidebar item** under Listings, with a count of listings needing review. The count is cached for 60 seconds.
- **Access.** The queue is open to super admins and to staff with the existing "edit listings" permission (`admin.post.edit`). Its action names are not CRUD names, so the panel's route-permission parser adds nothing, and the `ModerationQueueAccess` middleware decides.
- **Admin alerts.** `AdminModerationAlert` emails staff when a listing goes to review. It sends at most 20 an hour, and the next alert after that says how many were left out. A child safety flag is always sent, marked urgent, even in shadow mode. It can be switched off in Settings > AI > Moderation.
- **Sweep.** Hand-overs now go through the same path, so they alert too.

Differences from the plan above:
- **A plain admin page, not a panel list.** The queue needs custom buttons, a form and a history view, and the panel's list would have needed methods on the core `Post` model.
- **Alert recipients.** The alerts go to every staff user, the same group as the app's "new listing" admin email.

Core files touched, each marked `adniro:ai`:
- `resources/views/admin/layouts/partials/sidebar.blade.php`: the queue item under Listings

Not checked in a signed-in browser: the queue, reject form and history were checked through HTTP tests as a super admin, a listing editor and other staff. Signed-out requests to `/admin/moderation` and the approve action were checked on the local site and are sent to the login page.

### Phase 6, quick post from text: built on branch `ai` (2026-11-20)
Committed as `32820647`.
Off by default. Covered by 21 more tests in `tests/Feature/Ai/ListingAssistantTest.php`, so 123 in all.

The whole path was also checked in the browser on the local site. A temporary local model pointed at a mock OpenAI-compatible server on `127.0.0.1`, so no outside service was called. "Create Listing" opened the composer, a sentence went through a real HTTP request, and the step 1 form opened with the banner. It had the category, title, description, price, tags, city, and both custom fields filled, with Check badges where confidence was below high. Stopping the mock showed the fallback, with the text in the description of the normal form. The composer was also checked at phone width. The temporary model, settings and rows were removed afterwards.

What exists:
- **Composer** at `/posts/create/quick`. It has a text box with a counter, "Use my location", "Create my listing", the remaining AI posts, and "Fill the form myself". A progress overlay shows while drafting, with a 25-second server limit and a 30-second browser limit.
- **"Post an ad"** links open the composer when quick post is available, through a guarded change in `getCreateListingLinkInfo()`.
- **Draft.** One synchronous call to the quick post model. `DraftPrompt` sends the fixed instructions in `resources/ai/listing-assistant-instructions.md` plus the catalog as one stable system block, and the user's text inside `<listing_input>` tags.
- **Catalog.** `CatalogBuilder` lists active leaf categories with their inherited field ids, then fields with option ids. It is in English, sorted and cached, and rebuilt when categories, fields, options or category fields change. File, video, date-range and URL fields are left out. From local data it is about 10 KB, roughly 3,000 tokens.
- **Sanitizer.** `DraftSanitizer` enforces the rules in section 6, adds Check and Please add marks, strips links, emails and phone numbers, and takes the leading number from values like "128 GB".
- **Location.** `LocationResolver` tries, in order, the nearest active city to the browser position within about 100 km, then a city named in the text, then the user's last listing. Everything stays inside the current country.
- **Into the form.** `DraftToPostInput` writes `postInput` in the exact step 1 shape, including the city keys the request adds and `cf` in the form's own format. It keeps contact details already typed. The marks go in `quickPostMarks`.
- **Step 1 banner** (`front.post.quick.form-banner`) with field highlights, a multiple-item note, soft policy warnings and "Not right? Describe it again". It also loads the draft's category, because the form only restores a chosen category after a validation error.
- **Fallbacks.** When the AI fails, the text goes into the description of the normal form, and fields from an earlier draft are dropped. Input that is not a listing stays on the composer with a request for one sentence. Text that is too short is refused with no AI call.
- **AI post limit.** It is set in Settings > AI > AI listing assistant, separately for members (by account) and guests (by IP), per day, week, month or with no limit. It uses calendar periods in the site timezone. A per-person lock stops parallel drafts. A changed limit applies at once.
- **Per-user override and reset.** An "AI limit" button on Admin > Users opens `/admin/users/{id}/ai-post-limit`, for super admins and staff who can edit users. This adds `users.ai_post_limit` and `users.ai_post_reset_at`.
- **Burst limit.** At most 6 drafts a minute per IP and 10 per user.
- **`post_drafts` table** with the input, the raw and sanitised output, status, counted flag, re-draft count, linked usage log and expiry.

Differences from the plan above:
- **What is counted.** The limit counts successful drafts in `post_drafts`, not rows in `ai_usage_logs`, because only the drafts know which re-drafts are free.
- **Guests are off by default.** The site has no captcha configured, so the guests setting starts off, as decision 3 recommends.
- **No GeoIP step yet.** The installed GeoIP data gives the country, not a city.
- **No quick post evaluation set yet.** The person checks every draft, so assigning a quick post model only requires a passed "Test model". The 40-input evaluation from section 9 is still to do.
- **Draft purge.** Expired drafts are not purged yet. That comes with the photo uploads in phase 7.
- **Front-end tests.** These tests leave out the app's localization middleware and set the country themselves. The test database has no countries or languages, and front-end requests there end in the app's `redirectUrl()` exit.

Core files touched, each marked `adniro:ai`:
- `routes/web/front.php`: one include line
- `app/Helpers/Services/Functions/core.php`: "Post an ad" points to the composer when available
- `resources/views/front/post/createOrEdit/multiSteps/create/post.blade.php`: one banner include
- `app/Http/Controllers/Web/Admin/UserController.php`: one line for the "AI limit" button
- `app/Services/Ai/Support/SchemaRules.php` (from phase 1): nested empty values are valid too

### Phase 7, quick post photos: built on branch `ai` (2026-11-20)
Committed as `5466c9bc`.
Covered by 11 more tests in `tests/Feature/Ai/QuickPostPhotosTest.php`, so 134 in all. The quick post fixtures are now shared in `tests/Feature/Ai/ListingAssistantFixtures.php`.

Checked in the browser on the local site against the local mock model, as in phase 6:
- A 3000 × 2000 test image was given to the photo input. It was shrunk in the browser and uploaded, and its server thumbnail appeared in the composer.
- With no text, "Create my listing" was enabled. The draft request carried the photo as a JPEG.
- The form opened prefilled, and the photos step already showed the photo with no second upload. It was stored at 1000 × 667, about 52 KB, after the site's own resize.
- Removing the photo from the composer deleted it.

The temporary local setup was removed afterwards.

What exists:
- **Photo tile in the composer.** It has "Add photos" with up to the site's picture limit, thumbnails and remove buttons. The browser shrinks each photo to a 1600 px JPEG before upload, which also converts HEIC where the browser can read it. Photos are uploaded one per request.
- **`POST posts/create/quick/photos`.** It validates against the site's image formats and size, stores through `TmpUpload::image()` in `temporary/{session uid}`, and appends to `session('picturesInput')`. Those are the folder and key the photos step uses. A HEIC file the server cannot read gets a clear message. Removing a photo uses the photos step's own delete action.
- **Photos to the model.** Up to 4 photos are re-encoded at 1024 px. The prompt says how many are attached. With a photo, the text is optional. The draft row keeps the photo paths.
- **Photo rules** in `resources/ai/listing-assistant-instructions.md`. The model uses what is clearly visible, trusts the text when the two disagree, and never copies contact details from photos or describes people. A blurry photo leads to a request for a clearer one. The prompt version is now `2026-11-20.2`.
- **Fallbacks keep the photos.** When the AI fails, the photos stay for the normal form. When there is no text, the description is left alone.
- **`ai:listing-assistant-purge`, run hourly.** It deletes expired drafts, and temporary uploads older than 48 hours from quick post and the normal form alike, with any folders left empty. It has a `--dry-run` option. This is decision 13, and it ends the old leak of abandoned uploads.

Found and fixed while checking:
- **An emptied template.** An editing slip emptied the composer template. The tests did not see it because they render only the form partial, while the page needs the site layout. A new test checks the compiled composer template for its form, overlay and script hooks.
- **Line comments in inline scripts.** The app's HTML minifier removes line breaks when it is switched on, which would break `//` comments in inline scripts. The quick post scripts use block comments only, and the new test checks this.

Core files touched, each marked `adniro:ai`:
- `app/Console/Kernel.php`: one schedule line for the purge

### Phase 8, quick post voice: built on branch `ai` (2026-11-20)
Committed as `5d5e64b3`.
Browser speech-to-text only; nothing is sent to the server except the resulting text. Covered by 3 more tests in `tests/Feature/Ai/ListingAssistantVoiceTest.php`, so 137 in all.

Checked in the browser on the local site. The pane's Chromium has speech recognition, so the mic and the language picker appeared, with "English (US)" first and the site's other languages after it. Real speech needs a microphone and Google's service, which the pane cannot provide, so a scripted recognizer stood in. It showed that interim words appear live and final words stay, that text typed earlier is kept, and that the chosen language is passed to the recognizer. The picker is locked while listening, and stopping resets the button. A blocked microphone shows its message. The layout was also checked at phone width. Not checked: real Chrome on Android, iOS Safari and Firefox.

What exists:
- **A mic button inside the text box,** with a speech language picker. It is shown only when the browser has `SpeechRecognition` or `webkitSpeechRecognition` and the page is in a secure context. Otherwise a hint points to the keyboard's dictation key.
- **Live transcript.** Interim results show as the person speaks, final results are kept, and the text stays editable. It joins what was already typed and respects the length limit.
- **Hard stop after 60 seconds,** with a message. Tapping the mic again stops it, and so does sending the draft.
- **Clear messages** for a blocked microphone, no speech heard and an unavailable service.
- **Speech language.** The site language in the current country comes first, for example `bn-BD` or `en-US`, then the other active site languages by their locale tag. The choice is remembered in the browser.
- **A privacy line** that appears with the mic: in Chrome, the audio is processed by Google.

Found and fixed while checking:
- **A multi-line array inside `@json(...)`.** It compiled into invalid PHP, and the composer returned a 500. The array now lives in a `@php` block, and the template guard tests parse the compiled composer with `token_get_all(..., TOKEN_PARSE)`.

No core files were touched in this phase.

### Phase 9, polish: built on branch `ai` (2026-11-20)
Committed as `afed6057`, together with the fixes listed under "After phase 9".
Covered by 11 more tests in `tests/Feature/Ai/AiPolishTest.php`, so 148 in all.

What exists:
- **Translations.** The user-facing AI texts are translated into the 8 other active languages (fr, es, ar, de, it, ru, hi, bn) in `lang/{code}/ai.php`: the rejection email and SMS, owner banners and badges, the finish line, statuses, category names, and every quick post screen. That is 91 texts per language. Admin screens fall back to English key by key. A test checks that every language has every text with the same placeholders and plural parts. For Arabic, Russian, Hindi and Bengali, counts are phrased like "left today: 3 of 5" so they read correctly for every number.
- **Mobile API.**
  - `GET /api/listing-assistant/limit` gives whether quick post is available and the person's limit.
  - `POST /api/listing-assistant/drafts` takes text, up to 4 photos, an optional position and a re-draft token. The photos are sent to the model and not stored.
  - The draft comes back in the same field names as `POST /api/posts`, plus the city, marks, policy flags and the limit.
  - At the limit it answers 429 with `Retry-After` and the reset time. When quick post is off it answers 403.
  - Members are identified by their Sanctum token, guests by IP.
- **AI spend widget** on the admin dashboard, for super admins. It shows today's spend, calls and failures, this month's spend, the share of the daily budget used, calls and cost per feature, the last 7 days, listings needing review, rejections today and quick post drafts today.
- **Quick post evaluation.** `php artisan ai:listing-assistant-eval {model?}` runs the 40 inputs in `resources/ai/eval/listing-assistant.json`. They cover 36 listings in 7 languages and 4 non-listings, and category answers are matched by English leaf name because ids differ between sites. The gate is at least 80% right categories, no invented price, every non-listing recognised and at most one listing refused. A full run sets or clears `listing_assistant_eval_passed_at`.
- **Evaluations column.** The AI model list shows each model's last passed moderation and quick post evaluation.
- **One-screen review**, on by default and controlled by Settings > AI > Quick post > "Review and publish on one screen". After a draft, the step 1 form opens at `/posts/create/quick/review`, with the photos shown above it and a Publish button.
  - Publishing validates with the step 1 request and saves with the same code as the last step of the normal form.
  - When photos are mandatory and there are none, it returns to the review.
  - When a package or payment step is needed, it continues to the photos step as usual.

Checked in the browser against the local mock:
- A draft with a photo opened the one-screen review, with the photo shown, the category and custom fields loaded, and a "Publish listing" button.
- Publishing was not pressed. The local site sends real email through SMTP, and a real listing would have emailed real addresses. Publishing is covered by the step 1 request and the existing save code, and the photo check is covered by a test.

Found and fixed while checking:
- **The button label.** The category script resets the step button to "Next" whenever a category is applied. On the one-screen review, the banner script restores "Publish listing".
- **API wording.** The API reused a web message that said the text was put in the normal form. A neutral "not available" message, `qp_off`, is used instead, in all 9 languages.
- **Controller reuse.** The router keeps controller instances between requests, so the API controller looks up the quick post service on each call instead of holding it.

Not done:
- **GeoIP city lookup.** The installed GeoIP data gives the country only.
- **Translations of admin screens.** Admins see English.

Core files touched, each marked `adniro:ai`:
- `routes/api.php`: the quick post route group, before the catch-all
- `resources/views/admin/dashboard.blade.php`: one include for the spend widget

### After phase 9 (2026-11-20)
- **API keys are now plain text.** At the owner's request, provider keys are stored and shown in full to admins: in the provider list and prefilled in the edit form. Before this, keys were encrypted with `APP_KEY`. Keys saved before an `APP_KEY` change could no longer be read, and saving a new key over them failed with "The MAC is invalid". Migration `2026_11_20_001000_store_ai_provider_keys_as_plain_text` decrypts readable keys and clears unreadable ones. The two local keys were unreadable and have to be entered again. Keys are still left out of JSON output and redacted from error messages. Emptying the field removes the key, and the provider then uses the `.env` key. Trade-off: anyone with database, backup or super admin access can read the keys.
- **Honest evaluations.** In both evaluation commands, a failed call now always counts as a failure, shows its reason, and blocks a pass. A run stops after the first 3 calls fail the same way, and leaves the model's result unchanged.
- **Unsent calls are not counted.** A call stopped before sending, such as with a missing key, no longer counts toward the daily call limit.

### Phase 10, server-side speech-to-text: built on branch `ai` (2026-11-20)
Optional, and off until an admin assigns a speech-to-text model in Settings > AI > Models. Covered by 10 more tests in `tests/Feature/Ai/TranscriptionTest.php` and `tests/Feature/Ai/ListingAssistantVoiceServerTest.php`, so 161 in all.

What exists:
- **`AiClientContract::transcribe()`,** implemented in `LaravelAiClient` with the Laravel AI SDK's `Transcription`. It uses the same provider registration, master switch, budget and call limit, usage log (feature `transcription`) and error redaction as the other features. The language is sent as an ISO 639-1 code, for example `bn` from `bn-BD`.
- **Test model for speech models.** A model that handles speech-to-text and no structured output is tested with one second of generated silence. Any answer, even an empty one, passes.
- **ElevenLabs.** It is added as a provider driver (`eleven`, key `ELEVENLABS_API_KEY`), for speech-to-text only. OpenAI, Gemini, Mistral, Groq, OpenRouter and OpenAI-compatible providers also offer speech-to-text through the SDK. Anthropic does not.
- **Recorder fallback in the composer.** Browsers with their own speech recognition keep using it, because it is free and live. Others, such as Firefox and some in-app browsers, show the mic when a speech model is assigned.
  - It records with `MediaRecorder` for up to 60 seconds and uploads to `POST posts/create/quick/transcribe`.
  - The text is added to the box, with contact details removed.
  - A line says the recording goes to the AI service chosen by the site.
- **API:** `POST /api/listing-assistant/transcriptions`. The limit endpoint now also reports whether server voice is available.
- **Limits.** Recordings are capped at 5 MB, with 10 a minute per person. They are not counted as AI posts. They are counted in the AI budget and the daily call limit.
- **Texts.** The recorder's four messages are in all 9 languages.

Not checked in a browser: the browser pane has its own speech recognition, so it never shows the recorder. That path is covered by tests of the endpoint and the composer template, and should be tried once in Firefox.

## 1. What we are building

| | Feature | Result for the user |
|---|---|---|
| A | **AI moderation** | Every listing is read by an AI model when it is created and again when its text, custom fields or photos change. Clean listings go live with no human step. Listings with drugs, scams and similar content are hidden, marked not approved, and the owner gets an email saying why. |
| B | **Quick post** | A user starts a listing from photos, voice or one rough sentence. AI fills in title, description, category, custom fields, price, location and tags. The user checks it and publishes. The normal form stays one tap away. |
| 0 | **Shared AI foundation** | One internal AI contract, providers and models managed in the admin panel, one usage log, one set of cost caps. Both features use it. |

## 2. The eight decisions that shape everything

1. **Moderation state gets its own column.** `posts.reviewed_at` cannot hold an AI verdict. Review is switched off today, so a null `reviewed_at` hides nothing. Saving the Listing Form settings page re-approves every unreviewed post when review is on (`app/Observers/Traits/Setting/ListingFormTrait.php:75-83`). Paid promotion sets `reviewed_at = now()` (`app/Helpers/Services/Payment.php:221-223`). So we add `posts.moderation_status` and leave the existing human-review feature alone.
2. **The new status is enforced inside the existing `ReviewedScope`.** 30 owner, edit, finish and payment flows already bypass that scope by name. Reusing it means owners can still open and fix a flagged listing while every public query hides it. A brand new scope would have to be added to all 30 places.
3. **Moderation is triggered by model observers, not by edits in each controller.** Web multi-step, web single-step and the API all end in the same `PostService::store()`, and photos are saved in that same call (`SubmitTrait.php:68-93`). Observers on `Post`, `Picture` and `PostValue` catch create, text edits, photo edits and API calls in one place.
4. **The AI check runs in a queued job, not in the web request.** A debounced, unique job per post means one AI call per burst of changes and no 5 to 20 second wait on submit. This needs one queue worker in production.
5. **When the AI cannot decide, the listing goes to a human.** A refusal, a timeout after retries, a broken response or a low-confidence answer all become `needs_review`. Nothing is auto-approved on an error.
6. **Quick post writes into the existing multi-step session.** The draft is put into `session('postInput')` and the photos into `session('picturesInput')`, exactly the shape the current form uses. Validation, guest auto-registration, verification, packages, payment and moderation then run through the one existing path. Quick post never creates a `Post` itself.
7. **Voice is speech-to-text in the browser.** Most text models do not take audio, and browser speech-to-text works with every provider. The browser Web Speech API turns speech into text in the same textarea, so the backend has one text path. Where the browser lacks it, the phone keyboard's dictation key does the same job. A provider with speech-to-text can be assigned later as a server-side fallback.
8. **Provider and model are chosen by the admin, per feature.** Providers, API keys and models are database records managed from the admin panel. Moderation and quick post each get their own primary and fallback model. Feature code talks to one internal contract. The first-party Laravel AI SDK sits behind it to reach OpenAI, Anthropic, Gemini, Mistral, Groq, xAI, DeepSeek, Ollama and OpenAI-compatible endpoints. A model must pass the evaluation sets in section 9 before it is used for enforcement.

## 3. What the code looks like today (verified)

- **Create:** `app/Services/Post/StoreTrait.php` saves the post (`:118`), then pictures (`:136`), then custom fields (`:142`). User input is copied with `$request->only($post->getFillable())` (`:65`), so new moderation columns must stay out of `$fillable`.
- **Edit:** text in `app/Services/Post/Update/SingleStepForm.php` and `MultiStepsForm.php`; photos through `PictureService` and the API picture endpoints (`routes/api.php:307-309`).
- **Validation runs only at form step 1.** `PostRequest` validates there; `storePost()` does not validate again. Pictures are validated only for single-step or API (`PostRequest.php:251`), custom fields only for web (`:365`).
- **Visibility:** `app/Models/Scopes/ReviewedScope.php:34-40` skips the admin panel and filters `reviewed_at` only when `listings_review_activation` is `'1'`. `isVerifiedPost()` in `app/Helpers/Services/Functions/admin.php:172-191` drives the show page, preview links and contact button.
- **Notifications:** `PostObserver::saved()` calls `sendNotification()` (`app/Observers/PostObserver.php:169-171, 201-247`). With review off, it sends the "your listing is online" mail (`PostReviewed`) as soon as a verified post is created. `BaseNotification` is queued on the `mail` queue.
- **Blacklist:** `BlacklistTitleRule` and `BlacklistWordRule` already run in `PostRequest` and reject before any AI call would happen. Nothing to add.
- **Settings pattern:** one `settings` row per group, backed by `app/Models/Setting/{Name}Setting.php`, stored as plain JSON and loaded into `config('settings.*')` at boot. A long-running queue worker keeps old settings until it restarts.
- **Current local values:** multi-step form, review off, guests can post, pictures mandatory with a limit of 5, email and phone verification off, captcha not configured, `QUEUE_CONNECTION=sync`, `CACHE_STORE=redis`.
- **Catalog size:** 134 categories (12 roots, 122 leaves), 26 custom fields, 176 options. Small enough to give to the model in one call.
- **Languages:** 17 `lang/` folders, 9 active languages (en fr es ar de it ru hi bn). Arabic is right-to-left.
- **Frontend:** jQuery 3.3.1 and Bootstrap 5.3.7, plain page scripts in `public/assets/js/app/`, no SPA.
- **Images:** GD driver, no Imagick, so no server-side HEIC decoding. `TmpUpload::image()` already fixes orientation and resizes to 1000 px.
- **Temp files:** nothing cleans `storage/.../temporary/`. Abandoned uploads from the current form already pile up.
- **Tests:** only `tests/TestCase.php` exists and the test database lines in `phpunit.xml:25-26` are commented out.
- **No AI provider** is in the codebase today.

## 4. Shared AI foundation

### How provider choice works
```
feature code  ->  AiClientContract  ->  model assigned to that feature  ->  Laravel AI SDK  ->  provider
```
- Feature code never names a provider or a model. It asks the contract for a structured result for a feature: `moderation`, `listing_assistant` or `transcription`.
- The contract looks up which model the admin assigned to that feature, loads that provider's key and base URL, and makes the call.
- If the primary model errors, times out or is rate limited, the fallback model for that feature is tried once.
- Changing provider or model is an admin action. No deploy is needed.

### Library behind the contract
`composer require laravel/ai`, the first-party Laravel AI SDK.

Confirmed in its Laravel 12 documentation:
- Text providers: OpenAI, Anthropic, Gemini, Azure, Groq, xAI, DeepSeek, Mistral, Ollama.
- Custom base URLs, which covers OpenRouter and gateways such as LiteLLM.
- Provider and model chosen per call.
- Structured output from a schema, without a dedicated class per call.
- Image attachments from a file path.
- Speech-to-text for OpenAI, ElevenLabs and Mistral.
- A list of providers for failover, and test fakes.

Not stated in that documentation, so confirmed in a short spike at the start of phase 1:
- Minimum PHP and Laravel versions and the package's release stability.
- How token usage, finish reason and safety blocks are exposed on the response.
- Whether an API key can be passed per call.
- Prompt-cache controls for providers that need explicit ones.

If a gap is real, the fallbacks are small. Keys are written into runtime config just before the call. Cost falls back to counting calls. A single provider can get its own small driver behind the same contract. Feature code does not change in any of these cases.

### Data model
```
ai_providers:  id, name, driver, api_key (encrypted), base_url NULL, active,
               last_tested_at, last_test_ok, last_test_error
ai_models:     id, ai_provider_id, model_id, label, supports_vision, supports_structured_output,
               supports_transcription, input_price_per_mtok NULL, output_price_per_mtok NULL,
               options json NULL, active, moderation_eval_passed_at NULL, listing_assistant_eval_passed_at NULL
ai_usage_logs: feature, ai_model_id, provider, model, user_id, ip, subject_type, subject_id,
               status, error_type, input_tokens, output_tokens, cached_tokens, cost_usd NULL,
               latency_ms, was_fallback, meta json, created_at
```
- `driver` is one of the SDK's providers, or `openai_compatible` with a base URL.
- `model_id` is the exact string the provider expects. The admin types it, or picks it from a "Fetch models" list where the provider offers one. No model IDs are hard-coded in the app.
- `options` holds optional provider settings such as reasoning effort, passed through untouched.

### Admin screens
| Screen | What the admin does |
|---|---|
| AI providers | Adds a provider, pastes its API key, sets a base URL if needed, switches it on or off, runs **Test connection** |
| AI models | Adds models under a provider, ticks capabilities, enters prices, runs **Test model**, which sends a small image and expects structured JSON back |
| Settings, AI | Assigns a primary and a fallback model to each feature, sets mode, budget and limits, flips the kill switch |

Settings on the Settings, AI screen:

| Key | Default | Notes |
|---|---|---|
| `enabled` | off | Master kill switch |
| `moderation_model_id` / `moderation_fallback_model_id` | none | Must be vision-capable |
| `listing_assistant_model_id` / `listing_assistant_fallback_model_id` | none | Must be vision-capable for photo drafting |
| `transcription_model_id` | none | Optional. Enables server-side voice |
| `daily_budget_usd` | 5 | All AI features together |
| `moderation_mode` | `shadow` | `off`, `shadow`, `enforce` |
| `moderation_fallback` | `approve` | What happens to a new post when AI is off or over budget: `approve` or `hold` |
| `auto_approve_min_confidence` | `high` | |
| `actor_daily_limit_moderation` | 30 | Per user or IP |
| `notify_admin_on` | `needs_review_and_rejected` | |
| `listing_assistant_enabled` | off | |
| `listing_assistant_guests_enabled` | on | See decision 3 in section 10 |
| `listing_assistant_user_limit` / `listing_assistant_user_limit_period` | 20 / `day` | AI posts per member. Period is `day`, `week`, `month` or `none` |
| `listing_assistant_guest_limit` / `listing_assistant_guest_limit_period` | 5 / `day` | AI posts per guest, counted by IP. Same period choices |
| `listing_assistant_max_images` | 4 | Images sent to the model |
| `listing_assistant_voice_enabled` | on | |
| `listing_assistant_policy_hints` | on | |

Guard rails when assigning a model:
- Moderation and photo drafting accept only models marked vision-capable that have passed Test model.
- Assigning a model to moderation while the mode is `enforce` shows a warning unless the moderation evaluation has passed on that model.
- A model with no prices entered can still be used. Its cost is logged as unknown and the budget falls back to a daily call limit.
- A feature with no model assigned is simply off. Moderation applies `moderation_fallback` and quick post is hidden.

### API keys
- Stored with Laravel's `encrypted` cast in `ai_providers`. Never in the `settings` JSON and never in the settings cache.
- Write-only in admin. The screen shows only the last four characters.
- Only a super admin can open the provider and model screens.
- Read fresh from the database for each call, so queue workers pick up a changed key or model without a restart.
- A key in `.env` still works as a fallback for a provider that has no stored key.
- Rotating `APP_KEY` makes stored keys unreadable. They must then be entered again.

### What the wrapper evens out between providers
| Difference | How it is handled |
|---|---|
| Structured output | Uses the provider's native schema mode through the SDK. The result is always validated again in PHP. Invalid JSON gets one repair retry, then counts as a failure |
| Refusals and safety blocks | Each provider signals these differently. All become status `refusal`. An empty or blocked answer is never an approval |
| Images | Sent as attachments from the resized file. Models without vision cannot be assigned to photo features |
| Reasoning effort | Optional per-model options. Left out where a provider has no such setting |
| Token usage and cost | Read from the response where present and priced with the admin's numbers |
| Prompt caching | The stable instructions always come first, which is what automatic caching needs. Explicit cache markers are added where the provider and the SDK allow |

### The contract
- `AiRequest`: `feature`, `systemBlocks`, `userContent`, `images`, `schema`, `maxTokens`, `timeoutSeconds`, `userId`, `ip`, `subjectType`, `subjectId`, `meta`. It carries no provider or model.
- `AiResult`: `ok`, `data`, `status` (`ok`, `refusal`, `truncated`, `error`, `capped`, `disabled`), `errorType`, `retryable`, token counts, `costUsd`, `latencyMs`, `provider`, `model`, `wasFallback`, `usageLogId`.
- The client never throws for provider problems. Moderation maps a bad status to `needs_review`. Quick post maps it to "fall back to the normal form".

### New files
| File | Purpose |
|---|---|
| `config/ai.php` | Queue names, timeouts and feature limits. No provider or model names |
| `app/Services/Ai/Contracts/AiClientContract.php` | `enabled(feature)`, `structured(AiRequest): AiResult`, `transcribe(path, locale)`, `budget(feature, userId, ip)` |
| `app/Services/Ai/LaravelAiClient.php` | Implements the contract on top of the Laravel AI SDK, handles fallback, writes one usage row per call |
| `app/Services/Ai/AiModelResolver.php` | Finds the primary and fallback model for a feature and loads the provider's key and URL |
| `app/Services/Ai/AiRequest.php`, `AiResult.php`, `BudgetDecision.php` | Small data objects |
| `app/Services/Ai/AiImage.php` | Resizes a stored picture to a 1024 px long edge for sending |
| `app/Services/Ai/CostCalculator.php` | Cost from token usage and the model's prices |
| `app/Models/AiProvider.php`, `AiModel.php`, `AiUsageLog.php` + migrations | The three tables above |
| `app/Models/Setting/AiSetting.php` + migration | The Settings, AI screen |
| `app/Http/Controllers/Web/Admin/AiProviderController.php`, `AiModelController.php` | The two admin screens with their test buttons |
| `app/Providers/AiServiceProvider.php` | Binds the contract, registers the moderation observers, loads `routes/web/ai-admin.php` |
| `app/Console/Commands/AiPing.php` | `php artisan ai:ping {model?}` runs the same check as Test model |
| `tests/Support/FakeAiClient.php` | Scripted results and errors for tests |

### Environment
```
AI_QUEUE_CONNECTION=redis
AI_QUEUE=ai
AI_HTTP_TIMEOUT=60
REDIS_QUEUE_RETRY_AFTER=180
```

### Cost caps
- Global daily budget: a Redis counter, rebuilt from the usage log if missing.
- Per person for AI posts: the admin-set limit and period described in section 6.
- Per user or IP per day for moderation checks.
- Per post: at most 10 moderation calls a day, so nobody can burn budget by editing back and forth.
- Over a cap there is no API call. Moderation applies `moderation_fallback`. Quick post sends the user to the normal form.

### Core files touched
`composer.json`, `.env.example`, `bootstrap/providers.php` (one line), `app/Observers/SettingObserver.php` (one method), the admin sidebar (two items).

## 5. Feature A: AI moderation

### Data model
```
posts.moderation_status  varchar(20) NOT NULL DEFAULT 'approved'   approved | pending | needs_review | rejected
posts.moderation_hash    char(64) NULL     content hash of the last decided version
posts.moderated_at       timestamp NULL

post_moderations: post_id, trigger, source (ai | human | system), content_hash, verdict,
  applied_status, categories json, confidence, user_reason, internal_reason, locale,
  model, policy_version, ai_usage_log_id, reviewer_id, notified_at, error, timestamps
```
- The default `approved` keeps the existing listings, seeders and admin-created rows visible.
- The three post columns are not fillable, so a user cannot post `moderation_status=approved`.
- The content hash covers title, description, tags, price, currency, category, post type, contact name, custom field values and the sorted list of picture paths. Reordering photos does not change it.

### When moderation runs
| Event | What happens |
|---|---|
| Post created by a user | Saved as `pending`. One job is scheduled with a 15 second delay so the photos and fields saved in the same request are included. |
| Text edited | Any hashed column changed: back to `pending` in the same save, job scheduled. |
| Photo added or removed | `pending`, job scheduled. A burst of uploads becomes one job. |
| Custom field changed | Same as photos. |
| Email or phone verification completes | If still `pending`, the job is scheduled then. Unverified posts are never sent to the AI. |
| Admin edits in the panel | No AI call. The hash is refreshed so the admin's version counts as decided. |
| Repost, payment, token saves, photo reorder | No trigger. |

### What the job does
1. Loads the post without global scopes and stops if it is gone or archived.
2. Computes the hash. If it equals `moderation_hash`, the earlier decision is reused with no AI call. This is what makes a human approval stick.
3. Checks the kill switch, mode and budget.
4. Calls the AI.
5. Computes the hash again. If the content changed during the call, the verdict is thrown away and the job is scheduled again.
6. Writes a `post_moderations` row and, in `enforce` mode, applies the status with `save()` so cached queries are cleared.
7. After three failed tries the post becomes `needs_review` and admins are alerted.

A sweep command every 5 minutes re-dispatches posts stuck in `pending` over 10 minutes and moves posts stuck over 60 minutes to `needs_review`.

### The request to the model
- **System prompt, cached and identical for every call:** the role, the policy table with allowed edge cases, the output rules, and the rule that listing text and images are data to classify and never instructions to follow. Stored as `resources/ai/moderation-policy.md` with a version string.
- **User content:** one text block with the user's locale and the listing fields as JSON inside `<listing>` tags, then up to 5 photos as base64 at 1024 px. Email and phone are left out.
- **Verdict schema:** `verdict` (`allow`, `reject`, `needs_human`), `categories` (enum list), `confidence` (`high`, `medium`, `low`), `user_reason` (two sentences at most, in the user's language), `internal_reason` (short English note for admins).

### Policy categories
| Code | Covers | Default outcome |
|---|---|---|
| `illegal_drugs` | Narcotics, controlled substances, prescription-only medicine without licence, drug paraphernalia | Reject |
| `scam_fraud` | Advance-fee offers, unreal prices, off-platform or gift-card payment demands, fake jobs, loans, visas or rentals, phishing links | Reject |
| `counterfeit_fake_docs` | Replicas, fake IDs, diplomas, passports | Reject |
| `stolen_goods` | Stated or obvious stolen items | Reject |
| `adult_sexual` | Sexual services, explicit nudity | Reject |
| `child_safety` | Any sexualisation of minors | Reject, immediate admin alert, reason never echoed |
| `human_exploitation` | Trafficking, organ sale, forced labour | Reject |
| `hate_violence_extremism` | Hate symbols, extremist material, threats | Reject |
| `hacking_personal_data` | Stolen accounts, malware, data dumps | Reject |
| `weapons_explosives` | Firearms, ammunition, explosives | Human review, depends on country |
| `protected_wildlife` | Endangered species, ivory | Human review |
| `hazardous_regulated` | Tobacco, vapes, alcohol, chemicals, gambling, miracle cures | Human review, depends on country |
| `spam_misleading` | Not a real offer, keyword stuffing, photos that do not match | Human review |
| `manipulation_attempt` | The listing tries to instruct the moderator | Human review |

### PHP decides the outcome, not the model
The model only labels. `VerdictMapper` applies fixed rules:
1. Refusal or a provider safety block: `needs_review`. Never approved.
2. Truncated or invalid JSON: one retry, then `needs_review`.
3. A reject-severity category with verdict `reject` and high confidence: `rejected`. Lower confidence: `needs_review`.
4. Any human-review category or a `needs_human` verdict: `needs_review`.
5. Verdict `allow` with no categories and enough confidence: `approved`. Anything else: `needs_review`.

`user_reason` is model output shown to a person, so it is stripped of tags, links and emails, cut to 300 characters, and escaped. The email leads with fixed translated category labels.

### What each status means
| Status | Public | Owner sees | Email |
|---|---|---|---|
| `pending` | Hidden | "We're checking your listing, usually under a minute" | None |
| `approved` | Live | Normal | The existing "listing is online" mail |
| `needs_review` | Hidden | "Our team is reviewing your listing" | None to the user, alert to admins |
| `rejected` | Hidden | Red banner with categories, reason and an Edit button | New `PostNotApproved` mail |

The rejection email states the listing title, the categories, the reason, an edit link, the fact that saving changes sends it for a new check, and a contact link. It is sent once per decision, in the user's language, and only to a verified address. Phone-only users get an SMS version.

### Fix found during verification: the "listing is online" email
Today `PostObserver::saved()` sends the "your listing is online" mail as soon as a verified post is created, because review is off. With moderation on, that mail would go out before the AI check, and a rejected user would get "online" followed by "not approved". The plan therefore adds one more core edit:
- In `app/Observers/PostObserver.php` `sendNotification()` (`:201-247`), send `PostReviewed` or `PostActivated` only when `moderation_status` is `approved`.
- Fire it when the status changes to `approved`, which is the moment the listing really goes live.
- In `shadow` mode nothing changes, because every post stays `approved`.

### Admin queue
- New `PostModerationController` with a list of `needs_review`, `rejected` and `pending` posts, oldest first, showing categories, confidence, both reasons, model and age.
- Buttons: Approve, Reject (with categories and a reason, sends the mail), Re-run AI, History.
- Approve stores the current hash, so the same content is never re-flagged.
- One sidebar item under Listings with a count badge.
- Admin alerts are throttled to 20 an hour, then a digest line.

### Core files touched
| File | Edit |
|---|---|
| `app/Models/Scopes/ReviewedScope.php:38` | Always-on `moderation_status = 'approved'` before the setting check |
| `app/Models/Post.php:364-385` | `verified` and `unverified` scopes include the new status |
| `app/Helpers/Services/Functions/admin.php:184-188` | `isVerifiedPost()` requires `approved`, a missing key counts as approved |
| `app/Http/Resources/PostResource.php:42` | Expose `moderation_status` |
| `app/Observers/PostObserver.php:201-247` | Gate the "online" mail on approval |
| `app/Console/Kernel.php` | One schedule line for the sweep |
| Views | Owner banner on the show page, status badge in My Listings, one line on the two finish pages, one admin sidebar item |

Every edit is marked `// adniro:ai` so a vendor update conflict is easy to find.

## 6. Feature B: Quick post

### Flow on a phone
1. **Post an ad** opens the composer at `/posts/create/quick`.
2. **Composer, one screen:** a large "Take or add photos" tile, a text box "Describe what you're selling", a mic button inside the text box, an optional "Use my location" chip, and one button "Create my listing". The button is enabled with at least one photo or ten characters of text. A "Fill the form myself" link is always visible.
3. **Drafting:** an overlay with progress text. Server limit 25 seconds, client limit 30.
4. **Review:** the existing step 1 form, prefilled, with a banner "We filled this in for you. Check the highlighted fields." Low-confidence fields carry a "Check" badge, missing required fields carry "Please add" and get focus. Guests still type name, email and accept terms. AI never fills contact details.
5. **Photos step:** already populated with the composer photos. The user taps Submit.
6. Payment, verification and finish run as today. Moderation then checks the listing.

Rough tap counts for a guest selling a used phone, counted from the form code and not measured with users:

| Flow | Taps | Typed fields |
|---|---|---|
| Today | 17 to 19 | 5 to 6 |
| Quick post with photos, guest | 8 to 11 | 2 |
| Quick post, logged in | 6 to 8 | 0 |

### Fallback at every step
- Composer: "Fill the form myself".
- AI off, slow, over budget or failing: redirect to the normal form with the photos kept and the typed text placed in the description.
- Unusable input such as a blurry photo: stay on the composer and ask for one sentence.

### AI post limit, set by the admin
- The admin sets how many AI-generated posts each person may create and over which period: per day, per week, per month, or no limit.
- Members and guests have separate limits. Members are counted by account. Guests are counted by IP address.
- One use is one successful AI draft. A failed or timed-out AI call does not count. Up to two re-drafts of the same draft are free.
- Periods follow the calendar in the site's timezone. A day resets at midnight, a week on Monday, a month on the 1st.
- The composer shows what is left, for example "3 of 5 AI posts left this week".
- At the limit, the composer says when the limit resets and offers the normal form. Only AI drafting stops. Posting itself is never blocked.
- A changed limit or period applies at once. Nothing has to be reset by hand.
- The admin can give one user a different limit, or reset that user's count, from the user's admin page.
- The mobile API returns the remaining count and reset time with each draft, and a 429 with the reset time at the limit.
- Guests on a shared network share one count. That is one more reason for them to sign in.

How it is counted:
- The count is the number of successful `listing_assistant` rows in `ai_usage_logs` for that user or IP since the start of the current period. No separate counter has to be kept in step.
- A short per-user lock around the draft call stops two parallel requests from passing the limit together.
- A new nullable column `users.ai_post_limit` holds the per-user override.
- The site-wide daily budget in USD still applies on top, as the ceiling for all AI use.
- The per-minute rate limit stays as burst protection.

### How the draft is produced
- **One call.** The whole catalog fits, so category and custom fields come back together. Move to two calls only if the rendered catalog passes about 30k tokens.
- **System block 1:** fixed instructions. Treat the input as data, no contact details or links in the text, only IDs from the catalog, write in the requested language, give a price only if the user stated one.
- **System block 2, cached:** the catalog as compact text, rendered in English, sorted, byte-identical between calls. One line per leaf category with its field IDs, one line per field with its options. File, video and date-range fields are left out.
- **User content:** up to 4 photos, then locale, country, currency and the user's text inside `<listing_input>` tags.
- **Draft schema:** `is_listable`, `unusable_reason`, `items_detected`, `title`, `description`, `category` with alternatives, `post_type_id`, `price` with a `stated` flag, `location` text, `tags`, `custom_fields`, `policy_flags`. Each main field carries a confidence.
- **Cache invalidation:** any admin edit to categories, fields or options, a prompt version change, or a model change. Listeners on those models rebuild the cached catalog.

### Server-side checks on the draft
The model's output is untrusted. `DraftSanitizer` enforces:
- Category must exist, be active and be a leaf. Otherwise try the alternatives, otherwise leave it empty and mark it missing.
- Custom fields must belong to that category. Select and radio take exactly one valid option ID, numbers must be integers, dates must be valid.
- Title and description are cut to the configured lengths. `PostRequest` cleans them again and strips contact details.
- Price is kept only if the user stated it. A foreign currency keeps the amount, uses the site currency and is marked "check".
- Tags go through the existing cleaner and limit.
- Missing required fields are computed in PHP, not trusted from the model.

### Location
First match wins, always inside the current country: browser position to nearest city, then the place named in the text, then the user's last listing, then GeoIP. Otherwise the existing city picker handles it.

### Photos
- The browser shrinks each photo to 1600 px JPEG before upload. That cuts upload size about ten times and converts HEIC wherever the browser can decode it.
- Uploads go through the existing `TmpUpload::image()` into the same temp folder and session key the photos step uses. The user never uploads twice.
- The server rejects HEIC it cannot decode with a clear message.
- A new hourly `quickpost:purge` command deletes expired drafts and their temp folders.

### Voice
- Uses `SpeechRecognition` where the browser has it: Chrome and Edge on desktop and Android, Safari from 14.1. Firefox and some in-app browsers lack it.
- Where it is missing, the mic is hidden and a hint points to the keyboard's dictation key.
- The transcript appears live in the text box and stays editable. A hard stop at 60 seconds.
- Recognition language follows the site locale and country, with a chip to switch.
- The permission prompt appears only on the first mic tap. HTTPS is required.
- In Chrome the audio is processed by Google's speech service. The interface says so in one line.
- Server-side transcription is a later, optional phase. It needs no new integration. The admin assigns a speech-to-text model from a provider that offers one.

### Backend surface
```
GET  posts/create/quick                    composer
POST posts/create/quick/photos             one photo per request
POST posts/create/quick/draft              returns JSON
GET  posts/create/quick/{draft}/apply      draft into session, redirect to the normal form
POST posts/create/quick/{draft}/publish    one-screen review, later phase
```
- Routes live in a new `routes/web/front/listing-assistant.php`, included from `routes/web/front.php` before line 120.
- `ListingAssistantController` extends the multi-step create base controller. Because the URL keeps `create` as its second segment, the existing package, payment and navigation logic applies unchanged.
- New services under `app/Services/QuickPost/`: `ListingAssistantService`, `CatalogBuilder`, `DraftPrompt`, `DraftSchema`, `DraftSanitizer`, `LocationResolver`, `DraftImageLoader`, `DraftToPostInput`.
- New `post_drafts` table: owner, session token, input, picture paths, raw and sanitised output, status, linked post, expiry.
- The draft call is synchronous inside the AJAX request. No queue is involved.
- Rate limit: 6 drafts a minute per IP, 10 per user, plus the admin-set AI post limit.
- Quick-post pages send no-cache headers and stay out of the guest page cache.
- The "check" and "missing" marks are kept in their own session key, so the stored form input keeps its exact current shape.

### Core files touched
| File | Edit |
|---|---|
| `routes/web/front.php` | One include line |
| `bootstrap/providers.php` | One line |
| `resources/views/front/post/createOrEdit/multiSteps/create/post.blade.php` | One banner include and one script include |
| `app/Helpers/Services/Functions/core.php:1626` | "Post an ad" points to the composer when quick post is on |
| `app/Console/Kernel.php` | One schedule line for the purge |

Quick post is hidden when the single-step form is selected. Supporting that mode is a later option.

## 7. How the two features meet

- Quick post never publishes. The final submit goes through the normal store path, where moderation runs. AI-drafted listings get no bypass.
- The draft call returns `policy_flags` at no extra cost. They are shown as a soft warning on the review screen and never block.
- Those flags use the same category codes as moderation, from one shared `Policy` class.
- After publishing, the user lands on their listing with the "checking" banner for roughly 15 to 40 seconds.
- Both features log to the same usage table and count against the same daily budget.
- Moderation is enforced before quick post is switched on. Easier posting brings more low-quality posts, so the filter comes first.

## 8. Implementation order

Each phase can ship on its own.

| # | Phase | Ships | Verify |
|---|---|---|---|
| 0 | Prerequisites | An API key for at least one provider, owner decisions from section 10, a MySQL test database in `phpunit.xml` | Tests can run |
| 1 | Shared foundation | Laravel AI SDK, internal contract, provider and model admin screens with test buttons, per-feature model assignment, usage log, caps | A provider added in admin passes Test connection, and `ai:ping` returns structured JSON from the assigned model |
| 2 | Moderation data model and hiding | Columns, scope edits, no AI yet | Setting a post to `rejected` by hand hides it everywhere public, the owner and admin still see it, saving settings or paying does not un-hide it |
| 3 | Moderation in shadow mode | Observers, job, prompt, sweep, evaluation command | One log row per burst of changes, none for reorder, admin edits or unverified posts. Evaluation set passes |
| 4 | Enforce and notify | Status applied, rejection email, owner banners, "online" mail gated | Clean post live in about 40 seconds, flagged post hidden with an email in the right language |
| 5 | Admin queue | Queue screen, approve, reject, re-run, alerts | Approval sticks with zero further AI calls on the same content |
| 6 | Quick post: text to draft | Composer with text and location, draft into the existing form, AI post limit with the remaining count shown | A sentence produces a prefilled form and a real post with custom fields. At the limit the composer offers the normal form |
| 7 | Quick post: photos | Photo upload in the composer, images to the model, purge command | Photos appear in the photos step with no second upload |
| 8 | Quick post: voice | Mic button | Works on Chrome Android and iOS Safari, hidden on Firefox |
| 9 | Polish | One-screen review, mobile API endpoints, translations for all locales, spend widget | |
| 10 | Optional | Server-side transcription through an assigned speech-to-text model | Owner decision |

Phases 6 to 8 depend only on phase 1, so a second developer could build them alongside phases 2 to 5. Quick post should still be switched on for users only after phase 4.

### Production requirements
- One worker: `php artisan queue:work redis --queue=ai --tries=3 --timeout=120 --max-time=3600` under Supervisor.
- `php artisan queue:restart` on every deploy.
- The scheduler cron that the app already needs.
- Redis and the phpredis extension on the server, as installed locally on 2026-11-19.
- One line in the privacy policy: listing text and photos are sent to the AI providers selected in admin for checking and drafting.

## 9. Testing

- **Unit:** verdict mapping for every branch, content hash behaviour, cost calculation, budget caps, catalog output being byte-identical between builds, schema containing no unsupported keywords.
- **Feature, with the fake AI client:** create, edit and photo changes each cause exactly one call. Public pages hide non-approved posts. Payment and the settings page cannot un-flag. A posted `moderation_status=approved` is ignored. The kill switch and caps stop all calls. Each AI failure sends quick post to the normal form with photos kept. The AI post limit blocks the next draft at the limit, resets at the period boundary, ignores failed calls, follows a changed period at once, and honours a per-user override. The draft applied to the session matches what the normal form stores.
- **Moderation evaluation set:** about 60 labelled listings. 30 clean ones including tricky legal items, 25 violating ones across all categories and in several languages, 5 prompt-injection attempts. Release gate: no violating listing auto-approved, at most 5% of clean listings held or rejected.
- **Quick-post evaluation set:** about 40 real-world inputs across phones, cars, apartments, jobs and electronics, in several languages, including blurry and multi-item photos. Reports category accuracy, required-field fill rate, latency and token use.
- Both evaluation commands take a model and run against the real provider. Every model is evaluated before it is assigned to a feature, and the admin model list shows each model's last result.
- **Provider layer:** the resolver picks the assigned model, falls back once on an error, never falls back on a refusal, and a missing assignment switches the feature off.

## 10. Decisions needed from you

| # | Question | Recommended default |
|---|---|---|
| 1 | Which provider and model for each feature at launch? | Your choice in admin. Use a vision-capable model that passes both evaluation sets |
| 2 | Should an edited live listing stay visible during the re-check? | No. Hide it for the short check. Posting clean and then editing in scam text is the classic attack |
| 3 | AI drafting for guests? | Yes with Turnstile and a low guest limit. Captcha is not configured locally, so without it keep guests off |
| 4 | When the AI is down or over budget, what happens to new listings? | Approve them, as today. The stricter option holds them for an admin |
| 5 | Site budget and moderation limits | 5 USD a day, 30 checks per user or IP, 10 per listing |
| 6 | Country-dependent items such as weapons, alcohol, vapes, CBD | Send to human review |
| 7 | Email the user when a listing waits for human review? | No. Banner only. Email on rejection |
| 8 | Appeal button on a rejected listing | Yes, once per content version |
| 9 | Promoted listing gets rejected | Keep the payment, it stays featured once fixed. State this in the terms |
| 10 | "Post an ad" opens the composer by default | Yes, with the "Fill the form myself" link |
| 11 | Prefill price only when the user states it | Yes. No AI price guessing |
| 12 | Voice | Browser speech now. Assign a server-side speech-to-text model later if mic use justifies it |
| 13 | Sweep all temp uploads older than 48 hours | Yes. It also fixes the existing leak |
| 14 | Rollout | One week in shadow mode, then enforce, then quick post |
| 15 | Library behind the internal contract | The first-party Laravel AI SDK. An own driver only for a provider where it falls short |
| 16 | Which providers to set up first | The ones you hold keys for. Anthropic, OpenAI and Gemini are all covered, plus an OpenAI-compatible entry for services such as OpenRouter |
| 17 | Who may manage providers, keys and models | Super admin only |
| 18 | Same model for both features, or one each | One each. Moderation needs accuracy, drafting needs speed |
| 19 | AI post limit for members and for guests | Your numbers in admin. Starting point: 20 per day for members, 5 per day for guests |
| 20 | Calendar periods or rolling windows | Calendar periods. "Resets on Monday" is easy to explain to users |
| 21 | Does a re-draft count as a use? | No, up to two per draft |
| 22 | Higher AI limits for paid packages | Later, as an option. The site already has packages, so a per-package limit is a small addition |

## 11. Cost

Cost depends on the model the admin assigns. The figures below are a reference for Claude Opus 5.5 at list prices. They are estimates from token arithmetic, not measurements. Shadow mode will give real numbers for whichever model is in use.

| Call | Estimate on Opus 5.5 |
|---|---|
| One moderation check with 5 photos | about 0.02 to 0.04 USD |
| One quick-post draft with 4 photos | about 0.05 to 0.10 USD |

Every call is logged with its tokens and priced with the per-model prices entered in admin, so the spend widget stays correct after a model change.

## 12. Risks

- **Vendor updates.** About 15 small edits land in core LaraClassifier files. All carry the `// adniro:ai` marker and are listed in sections 4 to 6.
- **Worker down.** Listings would sit in `pending`. The sweep command moves them to human review and alerts admins.
- **False rejections.** The shadow week, the evaluation gate, the human-review middle state and the appeal button all limit this.
- **Prompt injection.** Listing text and photos are delimited as data, the output is schema-bound, and PHP makes the final decision and validates every ID.
- **Spend.** Global, per-person and per-listing caps, image limits and a kill switch.
- **Model quality varies.** A different model can approve what another rejects. The evaluation gate and a fresh shadow run cover every switch.
- **Provider safety filters.** Some providers block prompts that contain drug or adult content, which is exactly what moderation must read. Those calls become human review, so a provider that blocks often is a poor fit for moderation. The evaluation set shows this before launch.
- **Keys in the database.** They are encrypted, write-only and limited to the super admin.
- **SDK gaps.** Four points are flagged in section 4 to confirm in a phase 1 spike, each with a fallback.
- **Data leaves the site.** Listing text and photos go to whichever provider is selected. A self-hosted model through Ollama keeps them on your own server.
