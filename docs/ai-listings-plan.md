# AI for listings: moderation and quick post

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
- Feature code never names a provider or a model. It asks the contract for a structured result for a feature: `moderation`, `quickpost_draft` or `transcription`.
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
               options json NULL, active, moderation_eval_passed_at NULL, quickpost_eval_passed_at NULL
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
| `quickpost_model_id` / `quickpost_fallback_model_id` | none | Must be vision-capable for photo drafting |
| `transcription_model_id` | none | Optional. Enables server-side voice |
| `daily_budget_usd` | 5 | All AI features together |
| `moderation_mode` | `shadow` | `off`, `shadow`, `enforce` |
| `moderation_fallback` | `approve` | What happens to a new post when AI is off or over budget: `approve` or `hold` |
| `auto_approve_min_confidence` | `high` | |
| `actor_daily_limit_moderation` | 30 | Per user or IP |
| `notify_admin_on` | `needs_review_and_rejected` | |
| `quickpost_enabled` | off | |
| `quickpost_guests_enabled` | on | See decision 3 in section 10 |
| `quickpost_user_limit` / `quickpost_user_limit_period` | 20 / `day` | AI posts per member. Period is `day`, `week`, `month` or `none` |
| `quickpost_guest_limit` / `quickpost_guest_limit_period` | 5 / `day` | AI posts per guest, counted by IP. Same period choices |
| `quickpost_max_images` | 4 | Images sent to the model |
| `quickpost_voice_enabled` | on | |
| `quickpost_policy_hints` | on | |

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
- The count is the number of successful `quickpost_draft` rows in `ai_usage_logs` for that user or IP since the start of the current period. No separate counter has to be kept in step.
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
- Routes live in a new `routes/web/front/quick-post.php`, included from `routes/web/front.php` before line 120.
- `QuickPostController` extends the multi-step create base controller. Because the URL keeps `create` as its second segment, the existing package, payment and navigation logic applies unchanged.
- New services under `app/Services/QuickPost/`: `QuickPostService`, `CatalogBuilder`, `DraftPrompt`, `DraftSchema`, `DraftSanitizer`, `LocationResolver`, `DraftImageLoader`, `DraftToPostInput`.
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
