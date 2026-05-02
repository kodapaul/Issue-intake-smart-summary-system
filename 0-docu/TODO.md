# TODO — Issue Intake & Smart Summary System

Waterfall plan. Phases run in order. Check items off (`[x]`) only when verified working. Add newly discovered tasks to the relevant phase.

**Legend:** `[ ]` not started · `[~]` in progress · `[x]` done & verified

---

## Phase 0 — Project Setup ✅
- [x] Install Laravel 12 (API-only) via `composer create-project`
- [x] Run `php artisan install:api` (Sanctum installed, unused)
- [x] Confirm SQLite as DB driver (default `.env`)
- [x] Create `CLAUDE.md` working agreement
- [x] Create `0-docu/TODO.md` (this file)
- [x] Strip unused frontend files (`resources/views`, `resources/js`, `resources/css`, `vite.config.js`, `package.json`) — API-only
- [x] Empty `routes/web.php` (welcome route removed)

---

## Phase 0.5 — Domain-Modular Restructure ✅
Moved from default Laravel layout to domain-grouped layout. All new files land in their domain folders from Phase 1 onward.

- [x] Move `app/Models/User.php` → `app/User/Models/User.php` and update namespace to `App\User\Models`
- [x] Update `config/auth.php` and `database/factories/UserFactory.php` to point at new namespace
- [x] Delete empty `app/Models/` directory
- [x] Create empty `app/Issue/` skeleton: `Enums/`, `Models/`, `Http/{Controllers,Requests,Resources}/`, `Services/Summary/`, `Providers/` (with `.gitkeep`)
- [x] Cleanup: deleted leftover `app/Enums/Priority.php` (wrong location), `app/Http/Controllers/Controller.php` (Laravel 12 doesn't need a base controller), and now-empty parent dirs
- [x] `composer dump-autoload` clean, `php artisan route:list` works, `php artisan migrate:fresh` runs cleanly
- [x] Decision: keep `app/Providers/AppServiceProvider.php` at conventional Laravel location (framework-level, not domain-specific). Domain-specific providers live in `app/<Domain>/Providers/`.

---

## Phase 1 — Domain Foundation (Issue Enums, Migration, Model)
All paths are inside the new modular layout: `app/Issue/...`
- [x] Create `app/Issue/Enums/Priority.php` (low, medium, high) — verified via tinker
- [x] Create `app/Issue/Enums/Status.php` (open, in_progress, resolved, closed) — verified via tinker
- [x] Create `app/Issue/Enums/Category.php` (bug, feature_request, support, incident, other) — verified via tinker
- [x] Create migration `create_issues_table` with all 12 columns + indexes on filter columns (status, priority, category) — verified via `migrate:fresh`
- [x] Create `app/Issue/Models/Issue.php` — `namespace App\Issue\Models;` with `$fillable`, enum casts (Priority/Status/Category), datetime casts (due_date/escalated_at), includes `issuer`/`issuer_email` fields
- [x] Run `php artisan migrate:fresh` and confirm `issues` table with 14 columns
- [x] **Phase 1 verified end-to-end via tinker:** create + read with enum casting + issuer fields persist correctly

---

## Phase 1.5 — Category Normalization ✅
**Decision:** Promote `Category` from enum to a real lookup table. Categories carry a `severity_level` that drives issue priority. Priority becomes admin-controlled — public API never accepts priority input. Category by **slug** in API; FK by `category_id` internally.

- [x] Create migration `create_categories_table` (renamed timestamp to run BEFORE issues migration)
- [x] Create model `app/Issue/Models/Category.php` with severity cast to `Priority` enum, `hasMany(Issue::class)`
- [x] Create `database/seeders/CategorySeeder.php` with 5 entries (incident/high, bug/medium, support/medium, feature_request/low, other/low) — uses `updateOrCreate` for idempotency
- [x] Register `CategorySeeder` in `DatabaseSeeder.php` (also fixed `App\Models\User` → `App\User\Models\User` import)
- [x] Modify `create_issues_table` migration: `string('category')` → `foreignId('category_id')->constrained()->restrictOnDelete()`; index `category_id` instead
- [x] Delete `app/Issue/Enums/Category.php`
- [x] Update `Issue` model: drop Category cast, replace `category` with `category_id` in fillable, add `category()` belongsTo, `creating`+`updating` hooks query `Category::find()` directly to avoid stale relationship cache
- [x] Fix `database/factories/UserFactory.php` (import path) and `app/User/Models/User.php` (add `newFactory()` and `$model` so factory resolution finds the moved User class)
- [x] Update `StoreIssueRequest`: removed `priority` rule entirely; changed `category` rule to `exists:categories,slug`
- [x] Update `UpdateIssueRequest`: removed `priority` rule; slug-based category with `sometimes`+`required`+`exists`
- [x] Update `IssueResource`: nested category as `{slug, name, severity_level}` via `whenLoaded`; null-safe enums
- [x] `php artisan migrate:fresh --seed` clean — 5 categories seeded, FK constraint live
- [x] Verified end-to-end via tinker: priority auto-derived on create (incident → high), priority recalcs on category change (→ feature_request → low)

---

## Phase 2 — API Layer (Controllers, Routes) ✅
- [x] Create `app/Issue/Http/Requests/StoreIssueRequest.php`
- [x] Create `app/Issue/Http/Requests/UpdateIssueRequest.php`
- [x] Create `app/Issue/Http/Resources/IssueResource.php`
- [x] Create `app/Issue/Http/Controllers/IssueController.php` — index/store/show/update; eager-loads `category`; filters via `when()` on status/priority/category-slug; `findBySlug` for category resolution
- [x] Register `Route::apiResource('issues', ...)->only([...])` in `routes/api.php` (also dropped unused Sanctum `/api/user` route)
- [x] **Verified end-to-end via curl:** POST creates with auto-derived priority, GET list paginates, filters work for category/priority/status, PATCH triggers priority recalc on category change, validation returns structured 422 with errors for missing fields and invalid slugs

---

## Phase 3 — Business Logic (Escalation Rule) ✅
- [x] Created `app/Issue/Services/EscalationService.php` with `shouldEscalate()` and `evaluate()` methods
- [x] Wired into Issue model `creating` and `updating` lifecycle hooks (after priority recalc, before save)
- [x] One-way flag: once `escalated_at` is set, it stays set (audit trail preserved)
- [x] `is_escalated` boolean and `escalated_at` timestamp both exposed in `IssueResource`
- [x] Added `?escalated=true|false` filter to `IssueController::index` (composes with status/priority/category filters)
- [x] Verified all 5 cases via tinker: high+overdue → SET, high+future → null, low+overdue → null, high+overdue+resolved → null, future-pushed-to-past via update → SET
- [x] Verified filter via curl: ?escalated=true returns 1, ?escalated=false returns rest, combined filters work

---

## Phase 4.0 — Knowledge Base ✅
The KB is the foundation: rules-based service matches against it, LLM service uses it as grounding context. Read-only seeded data, public via `/api/playbook`.

- [x] Migration `create_playbook_entries_table` with JSON columns for triggers/steps/faqs
- [x] Model `PlaybookEntry` with JSON casts, slug as route key, `findBySlug()` helper, `@property` docblocks
- [x] `PlaybookSeeder` with 10 fully-fleshed entries — realistic support content (3-5 steps + 3 FAQs each)
- [x] Registered seeder in `DatabaseSeeder`
- [x] `PlaybookEntryResource` — `triggers` hidden by default, exposed via `?include_triggers=true` for internal AI use
- [x] `PlaybookController` — read-only index + show, error handling consistent with IssueController
- [x] Routes `apiResource('playbook')->only(['index','show'])` inside throttle group
- [x] `migrate:fresh --seed` clean — 10 entries seeded
- [x] Verified: GET /api/playbook returns 10 entries; GET /api/playbook/{slug} returns full content; 404 on missing slug

---

## Phase 4.1 — AI / Automation Layer ✅
Rules-first / LLM-escalation pattern. Rules engine handles the common cases instantly and deterministically; LLM only kicks in for low-confidence rules matches; both fall through to a generic fallback if the LLM is unavailable or fails.

- [x] `SummaryServiceInterface` — contract returning `{summary, suggested_action, matched_playbook_slug, confidence, source}`
- [x] `RulesBasedSummaryService` — token-scoring matcher against PlaybookEntry triggers; returns highest-scoring entry's content; "no match" → generic fallback (first sentence truncated)
- [x] `LlmSummaryService` — Anthropic Claude API call (default Haiku 4.5), 10s timeout, sends description + playbook catalog, asks LLM to pick best slug or "none"; throws on API failure
- [x] `CompositeSummaryService` — orchestrator: rules first, escalate to LLM only if confidence < 2, fall back to rules result on LLM failure with warning logged
- [x] `IssueServiceProvider` — binds `SummaryServiceInterface` to `CompositeSummaryService`, instantiates LLM only when `ANTHROPIC_API_KEY` is set
- [x] Registered provider in `bootstrap/providers.php`
- [x] Added `anthropic` config block in `config/services.php` and entries in `.env.example`
- [x] Wired into `IssueController@store` via method injection, summary + suggested_action persisted on create, source attribution logged
- [x] Verified via curl: strong match → rules (confidence ≥ 2), weak match → fallback (LLM not available without key), generic summary returned for novel descriptions
- [x] **Live LLM end-to-end verified:** Gemini (`gemini-2.5-flash-lite`) successfully classified a novel description ("wipe my profile and start fresh") to `account_settings` slug. `source=llm, confidence=99` confirmed. Rate-limit incident on `gemini-2.0-flash` validated graceful fallback behavior — exception caught, warning logged, rules result returned, user got a usable response.
- [x] **Multi-provider refactor (added):** `LlmProviderInterface` + `AbstractLlmProvider` with shared system prompt and slug-extraction logic; concrete providers for **Anthropic**, **OpenAI**, **Gemini**, **Perplexity**. `LlmSummaryService` is now provider-agnostic; selection driven by `LLM_PROVIDER` env. Recommend Gemini (`gemini-2.0-flash`) for free-tier demo.
- [x] **Prompt upgrade (added):** moved persona into proper `system` role (Anthropic top-level field; OpenAI/Gemini/Perplexity system message). Persona reads as expert support triage agent with explicit output rules ("only the slug, never explanations"). User message holds playbook catalog + customer description.

---

## Phase 5 — Seed Data & Testing ✅
- [x] Created `database/seeders/IssueSeeder.php` with 10 realistic, varied issues — mix of all 5 categories, all 4 statuses, 2 auto-escalated (overdue + high), some anonymous, varied `created_at` for realistic listing
- [x] Created `database/factories/IssueFactory.php` for use in tests
- [x] Registered `IssueSeeder` in `DatabaseSeeder` and removed `WithoutModelEvents` trait so priority-derivation + escalation hooks fire during seeding (the business logic IS what we want to demonstrate)
- [x] Wired `Issue` model with `HasFactory` trait + `newFactory()` method so factory resolution finds the non-default `app/Issue/Models/` path
- [x] Ran `migrate:fresh --seed` clean — 5 categories + 10 playbook entries + 10 issues
- [x] **17 PHPUnit feature tests, 63 assertions, all passing in 213ms:**
  - `IssueApiTest` (12 tests): create with rules-match, create with no-match fallback, validation 422, invalid category, XSS sanitization, list pagination, filter combinations, show with eager-load, 404 on missing, priority recalc on category change, optimistic-lock 409, escalation flag fires on overdue+high
  - `PlaybookApiTest` (5 tests): index lists 10 entries, triggers hidden by default, triggers exposed via query param, show returns full entry, 404 on unknown slug
- [x] Configured `phpunit.xml` to disable LLM env keys during tests (no accidental external API calls)

---

## Phase 6 — Documentation & Submission ✅
- [x] Replaced default Laravel `README.md` with humanized project README — quick start, endpoint table, inline-arrow workflows (no big ASCII diagrams), key architecture decisions, AI orchestration deep-dive (rules-first → LLM-escalation → fallback), multi-provider strategy pattern, "What I'd improve with more time" with 9 ranked items, pointers to TODO.md and CLAUDE.md
- [x] AI orchestration section explicitly covers: KB-as-source-of-truth, three-tier graceful degradation, why no tool-use/RAG at this scale, system vs user prompt separation
- [x] Verified fresh-clone flow: `composer install && cp .env.example .env && php artisan key:generate && touch database/database.sqlite && php artisan migrate:fresh --seed && php artisan serve` works end-to-end (17 tests + 10 seeded issues + 2 auto-escalated)

---

## Phase 7 — Frontend SPA (Vue 3 + Tailwind v4 + shadcn-vue) ✅
- [x] Re-installed frontend toolchain (Vite, Vue 3, TypeScript, Tailwind v4, laravel-vite-plugin)
- [x] Initialized shadcn-vue (style: new-york, base: slate); pulled in 12 component groups (button/input/textarea/label/select/table/dialog/tabs/badge/card/accordion/sonner)
- [x] Tailwind v4 + shadcn CSS variables wired in `resources/css/app.css`
- [x] Added `GET /api/categories` endpoint (`CategoryController` + `CategoryResource`) so the form's category dropdown is data-driven
- [x] Vue components: `IssueForm` (submit + show auto-summary), `IssuesTable` (filterable list with row click → modal), `IssueDetailDialog` (status update with optimistic locking via `if_unmodified_since`), `HelpCenter` (Grab-style KB browse with FAQ accordion)
- [x] `App.vue` shell with shadcn `Tabs` and Sonner `Toaster`; `main.ts` mount entry; typed `api.ts` fetch wrapper; full `types.ts` for Issue/Category/PlaybookEntry shapes
- [x] Laravel side: `routes/web.php` serves `resources/views/app.blade.php` (Vite-injected); `package.json` has `dev`/`build` scripts
- [x] Verified production build: `vite build` → 2431 modules, 455ms, no errors. Bundle: 307kB JS / 35kB CSS gzipped.
- [x] End-to-end: `/` serves SPA, `/api/categories` returns 5 entries, all REST endpoints reachable from the frontend

---

## Discovered / Backlog
*(items added here as they surface during the build)*

- [ ] **Phase 2.5 (added):** Observability — `LogApiRequest` middleware writes JSON-line logs to `storage/logs/api-YYYY-MM-DD.log`; `IssueController` wraps writes in `DB::transaction` + try/catch with structured error logging via private `handleError()` helper. Verified: 2xx → INFO, 4xx → WARNING, 5xx → ERROR. ✅
- [x] **XSS hardening (added):** `prepareForValidation()` in `StoreIssueRequest` and `UpdateIssueRequest` runs `strip_tags()` on `title`, `description`, `issuer`. Verified: script/img/anchor tags neutralized at input layer, plain text passes through untouched. README note pending: frontends must still escape on output (defense-in-depth).
- [x] **Concurrency + overload defense (added):**
  - Rate limiting: `throttle:60,1` middleware on apiResource → 60 req/min/IP, returns 429
  - Optimistic locking: `if_unmodified_since` field on `UpdateIssueRequest`. When sent, controller compares to current `updated_at`; on mismatch returns 409 with `current_updated_at` so client can retry. Optional (matches HTTP `If-Unmodified-Since` semantics).
  - Verified: stale timestamp → 409 with retry hint; matching timestamp → 200; missing field → 200 (no check); 65 rapid requests → ~60 succeed then 429.
