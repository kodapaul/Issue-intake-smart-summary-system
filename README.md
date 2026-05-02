# Issue Intake & Smart Summary System

A Laravel 12 + Vue 3 system for a support / operations team to triage incoming tickets. New issues get an auto-generated summary and a suggested next action. Works without an LLM key (rules-based fallback), gets smarter when one's plugged in (Claude, GPT, Gemini, or Perplexity). Includes a small SPA UI for submitting tickets, browsing the queue, and reading the knowledge base.

I wrote this in waterfall phases — there's a granular play-by-play in [`0-docu/TODO.md`](0-docu/TODO.md) if you want the full trail of decisions, missteps, and trade-offs. This README is the readable version.

> **Note on the monorepo shape.** As a developer I'd typically separate the frontend and backend into their own repositories with their own deployment pipelines. For this assessment I combined them in a single Laravel project for simplicity — fewer moving parts to spin up, one `git clone` and you're running the whole thing. In production I'd split them: a standalone API and a separate Vue SPA (probably static-deployed to Cloudflare Pages or similar) talking over HTTPS with proper CORS.

---

## Quick start

One-time setup:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite                # SQLite is the default driver
php artisan migrate:fresh --seed
npm install
```

Then run the backend and frontend in **two separate terminals** (both need to stay running):

**Terminal 1 — backend:**
```bash
php artisan serve
```

**Terminal 2 — frontend (Vite hot-reload):**
```bash
npm run dev
```

Open **`http://127.0.0.1:8000/`** in your browser — you'll land on the SPA with three tabs: **Submit issue**, **Issues**, and **Help center**.

> **Important:** always visit `:8000` (the Laravel server). Vite logs `http://localhost:5173` to its terminal — **don't visit that**, it's a build server, not your app. Laravel's `@vite` directive routes asset requests to Vite automatically while `npm run dev` is running.

If you'd rather not keep `npm run dev` running, use `npm run build` once instead — it bakes the assets into `public/build/` and Laravel serves them directly. No second terminal needed.

You can also hit the API directly without the UI: `http://127.0.0.1:8000/api/issues` returns the 10 seeded tickets as JSON, two of which are already auto-flagged for escalation.

### Optional — turn on the LLM layer

Pick a provider, drop in a key:

```dotenv
LLM_PROVIDER=gemini                            # anthropic | openai | gemini | perplexity
GEMINI_API_KEY=AIza...                         # or your provider's equivalent
GEMINI_MODEL=gemini-2.5-flash-lite             # confirmed on Gemini's free tier
```

Then `php artisan config:clear`. That's the whole switch — the architecture is provider-agnostic.

---

## API endpoints

| Method | Path | What it does |
|---|---|---|
| `POST` | `/api/issues` | Create an issue. Auto-generates `summary` + `suggested_action`, derives priority from category. |
| `GET` | `/api/issues` | List issues. Filter via `?status=`, `?priority=`, `?category=` (slug), `?escalated=true\|false`. Composes. |
| `GET` | `/api/issues/{id}` | Show one issue with eager-loaded category. |
| `PATCH` | `/api/issues/{id}` | Partial update. Recalculates priority if category changes. Optimistic-locking via `if_unmodified_since`. |
| `GET` | `/api/playbook` | List the 10 knowledge-base entries that drive the AI layer. |
| `GET` | `/api/playbook/{slug}` | Show one KB entry with FAQs + troubleshooting steps. |
| `GET` | `/api/categories` | List the 5 categories (used by the SPA's category dropdown). |

Rate-limited to 60 req/min/IP across all routes via the `throttle` middleware.

### Quick curl tour

```bash
# Create — watch summary + suggested_action come back populated
curl -s -X POST http://127.0.0.1:8000/api/issues \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"title":"Promo broken","description":"My code DISCOUNT10 keeps saying invalid","category":"support"}'

# Filter — only escalated, only high priority
curl -s "http://127.0.0.1:8000/api/issues?escalated=true&priority=high" -H "Accept: application/json"

# Optimistic locking — stale timestamp returns 409 + the current updated_at
curl -s -X PATCH http://127.0.0.1:8000/api/issues/1 \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"status":"resolved","if_unmodified_since":"2020-01-01T00:00:00+00:00"}'
```

---

## How a request actually flows

`HTTP request → throttle → middleware logger → form-request validation → controller (try/catch) → service layer → DB::transaction → Eloquent model (lifecycle hooks: priority + escalation) → API resource → JSON response`

That's the whole trip in one line. Every arrow is intentional — none are accidents of "Laravel happened to do this."

The interesting one is the **service layer** branch on issue create:

`description → CompositeSummaryService → RulesBasedSummaryService (always first) → high confidence? → return | low confidence? → LlmSummaryService (if API key set) → fallback to rules on failure`

Three tiers of degradation. Cheap path runs first; LLM only pays the latency/cost when the rules engine genuinely doesn't know what it's looking at.

---

## Frontend (the SPA at `/`)

Built with **Vue 3 Composition API + TypeScript + Tailwind v4 + shadcn-vue**. Three tabs covering the core flows:

| Tab | What it does | Endpoints used |
|---|---|---|
| **Submit issue** | Form with category dropdown; on submit, shows the auto-generated summary + suggested action inline | `GET /api/categories`, `POST /api/issues` |
| **Issues** | Filterable table (status / priority / category / escalated). Row click → modal with full detail + status update via optimistic locking | `GET /api/issues`, `PATCH /api/issues/{id}` |
| **Help center** | Grab-style knowledge-base browse — topic list on the left, selected topic detail on the right with FAQs accordion + troubleshooting steps | `GET /api/playbook`, `GET /api/playbook/{slug}` |

### Frontend flow

`browser → Vite-built JS bundle → Vue mounts on #app → fetch() → Laravel API → typed response → reactive UI`

The frontend is a pure consumer — it doesn't know anything about Eloquent, the LLM, or the rules engine. It speaks JSON to the same REST API documented above.

### Why combined, not separated

As mentioned at the top: in a real production system, I'd split this into a standalone Laravel API and a separate Vue SPA repo with its own deployment pipeline (Cloudflare Pages, Vercel, S3+CloudFront, etc.). The combined-repo shape here is a deliberate trade-off for the assessment — one `git clone` runs the whole stack with no CORS plumbing, no separate deploy steps, no environment-coordination headaches between two repos. For an MVP this is fine; for a team I'd separate them.

---

## Try it — sample inputs

Each example below targets a different code path. Copy-paste into the **Submit issue** tab and watch what happens.

### 1. Easy rules match (the "happy path" demo)
```
Title:        Cant apply promo code at checkout
Description:  My promo code SUMMER25 keeps saying invalid even though I copied it from the email. The discount wont apply at all.
Category:     Support
```
**Expect:** Auto-summary *"Customer reports an issue applying a promo or discount code at checkout."* — rules engine matched `promo`, `code`, `discount` triggers and pulled from the playbook.

### 2. Login problem (different topic, same path)
```
Title:        Cant log in to my account
Description:  I keep entering my password but it says invalid credentials. I just used the same password an hour ago. Tried clearing cache.
Category:     Support
```
**Expect:** Summary about *"difficulty logging in or completing account registration"* — `login`, `cant log in`, `password` triggers fire.

### 3. Critical incident (high-priority derivation)
```
Title:        Production checkout returning 500 errors
Description:  All checkout attempts on prod are failing with HTTP 500. Started 10 minutes ago. Customers cannot complete purchases.
Category:     Incident
```
**Expect:** Priority comes back as **high** (incident category → high severity → derived priority). The form doesn't capture a `due_date`, so this won't show as escalated — but the 2 pre-seeded escalated issues in the **Issues** tab demonstrate that path.

### 4. XSS scrubber test
```
Title:        Bug <script>alert('hi')</script> in dashboard
Description:  Click <a href="javascript:alert(1)">here</a> to <b>break</b> the page
Category:     Bug Report
```
**Expect:** All HTML tags stripped out — only plain text appears in the result. That's `strip_tags()` running in `prepareForValidation()` before validation/persistence.

### 5. Validation rejection
Leave **Title** blank, type *"too short"* in **Description**, don't pick a **Category**. Click submit.
**Expect:** Red toast — *"Please fix the highlighted fields"* — and red borders + error messages under each invalid input. That's the `StoreIssueRequest` rules firing and Laravel returning a structured 422.

### 6. Novel description (LLM kicks in, if configured)
```
Title:        Profile feels stale
Description:  The recommendation engine is showing me content based on who I was a year ago. I'd like to wipe my profile and start fresh, like a factory reset on the personalization.
Category:     Support
```
**Expect (without `LLM_PROVIDER` set):** Generic fallback summary — first sentence of the description, generic *"Review and route"* action.
**Expect (with `LLM_PROVIDER=gemini` set):** LLM classifies it as `account_settings`, returns the canonical content for that playbook entry.

To watch this happen live, tail the logs in another terminal:
```bash
tail -f storage/logs/api-$(date +%Y-%m-%d).log | grep summary_generated
```
You'll see `source=rules`, `source=llm`, or `source=fallback` per submission.

### Quick reference

| What you're testing | Pick |
|---|---|
| Rules engine working | #1, #2 |
| Auto-priority from category | #3 (high) vs #1, #2 (medium) |
| XSS defense | #4 |
| Validation 422 | #5 |
| LLM escalation | #6 |

After a few submissions, switch to the **Issues** tab — your new tickets appear at the top, filterable by status/priority/category/escalated. Click any row → modal opens with a status dropdown that demos the optimistic-locking PATCH endpoint.

---

## Architecture & key decisions

### Stack
- **Backend:** Laravel 12 + SQLite + Eloquent. SQLite because it's a single file the reviewer doesn't have to install or configure. Eloquent makes the choice driver-agnostic — switching to Postgres is a one-line `.env` change. For real production I'd pick Postgres for concurrent writes and JSONB indexing.
- **Frontend:** Vue 3 Composition API + TypeScript + Vite + Tailwind v4 + shadcn-vue. Components are copied into the project (`resources/js/components/ui/`) — no runtime UI dependency, you own the markup.
- **Repo shape:** combined backend + frontend in one Laravel project for assessment simplicity. In a production team I'd separate them — a Laravel API repo and a Vue SPA repo deployed independently.

### Domain-modular layout
Code is grouped by domain, not by technical role:

```
app/
├── Issue/                 ← the rich domain
│   ├── Enums/             ← Priority, Status (Category lives in DB now)
│   ├── Models/            ← Issue, Category, PlaybookEntry
│   ├── Http/{Controllers,Requests,Resources}/
│   ├── Services/{EscalationService, Summary/{rules, llm/*}}
│   └── Providers/         ← IssueServiceProvider — interface bindings
├── User/Models/           ← supporting infra; auth not in scope
├── Http/Middleware/       ← cross-cutting (LogApiRequest)
└── Providers/             ← framework-level (AppServiceProvider)
```

Why: each domain owns what it needs and nothing more. Adding a new domain = adding a top-level folder, no cross-cutting refactor. Empty folders aren't created "just in case" — they appear when there's actual code to put in them. The asymmetry between the rich `Issue/` and the slim `User/` is the signal: this is a single-bounded-context system with one core domain.

### Category as a normalized table, not an enum
Bug, Incident, Support, etc. live in the `categories` table with a `severity_level` column. Issues `belongsTo` Category. **Priority is derived from `category.severity_level`** at create time and recalculated on category change. The public API never accepts `priority` as input — it's admin-controlled by definition (a category change is the only way to influence it without auth).

Why: priority should reflect the *kind* of issue, not the submitter's opinion. Encoding the mapping in a table (instead of a `match` expression in PHP) means it's editable without a deploy and demonstrates real schema thinking.

### Escalation rule — service + lifecycle hook
An issue is escalated when `priority === High` AND `due_date` is past AND status isn't `Resolved` or `Closed`. The check lives in `EscalationService::evaluate()` and runs from the model's `creating` and `updating` hooks. Once `escalated_at` is set, it stays — preserving the audit trail even if the conditions later change. (No auto de-escalation; that would be an admin-controlled action.)

### XSS, rate limiting, optimistic concurrency
- `prepareForValidation()` runs `strip_tags()` on `title`, `description`, `issuer` so script tags die at the boundary
- `throttle:60,1` middleware applied as a route group — DoS budget per IP
- `if_unmodified_since` on `PATCH` returns `409 Conflict` with the current `updated_at` so clients can retry
- Defense in depth — the frontend should still escape on output; documented as a layered concern, not an excuse to skip either layer

### Observability
A `LogApiRequest` middleware writes JSON-line logs to `storage/logs/api-YYYY-MM-DD.log` for every request and response, with status code + duration + log-level varying by HTTP class (2xx info, 4xx warning, 5xx error). The controller wraps writes in `DB::transaction` and try/catch with a centralized `handleError()` that logs full context but returns a generic message to the client (no stack traces leaked). `jq -s . storage/logs/api-*.log` pretty-prints everything as a single JSON document for quick triage.

---

## AI orchestration — the part the rubric grades hardest

This is rules-first / LLM-escalation, not LLM-first. It's a deliberate cost and reliability choice.

`description → rules engine scores against playbook triggers → confidence ≥ 2? → return immediately | < 2 → LLM (if available) classifies via grounded rerank → return playbook entry's canonical content | LLM fails / unavailable → return rules fallback`

### The Knowledge Base is the system's brain
`playbook_entries` is a seeded table of 10 hand-written support topics — Login & Registration, Payment & Billing, Promo Codes, Delivery & Shipping, etc. Each entry carries:

`triggers (keywords) → summary_template → suggested_action → troubleshooting_steps → faqs → category_hint → priority_hint`

This is the **single source of truth** for both layers:
- **Rules engine** scores incoming descriptions against `triggers` and returns the highest-scoring entry's content
- **LLM** receives the catalog inline in its prompt and is asked to pick the best slug — never to invent free text

Same data, two consumers. The LLM can't hallucinate a category that doesn't exist because the prompt tells it to return one of these slugs or `"none"`. The rules engine can't make up content because it's pulling directly from the table.

### Three-tier graceful degradation
- **Tier 1 — rules engine** runs on every issue. ~50ms. Free. Deterministic. Handles the bulk of common cases (login, payments, delivery, etc.) at full quality.
- **Tier 2 — LLM rerank** kicks in only when rules confidence is below threshold. The LLM gets the description + the catalog and picks the best slug or returns `"none"`. ~1s, ~580 input tokens, ~$0.0006 per call on Gemini Flash Lite.
- **Tier 3 — generic fallback** is what we return if the LLM is unavailable, errors out, or returns `"none"`. First sentence of the description as the summary, "Review and route to appropriate team" as the action.

This was demonstrated live during build: when Gemini hit a 429 rate-limit, the system caught the exception, logged a warning, and returned the rules result. The user got a usable summary instead of a 500.

### Multi-provider abstraction (Strategy pattern)
The LLM layer is a strategy interface, not a single Anthropic client:

`LlmSummaryService → LlmProviderInterface → { AnthropicProvider | OpenAIProvider | GeminiProvider | PerplexityProvider }`

`LLM_PROVIDER=gemini` in `.env` swaps the provider — no code change. Each provider class isolates its API quirks (Anthropic uses a top-level `system` field; Gemini uses `systemInstruction`; OpenAI/Perplexity use a system message role). The shared `AbstractLlmProvider` holds the system prompt and slug-extraction logic so persona changes happen in one place.

Why: real teams pick LLM providers based on cost, latency, and contractual relationships. Hard-coding one would have been a tutorial-grade decision, not a production-shape one. Using Gemini as the default also keeps the demo free-tier-friendly.

### The system prompt — proper role separation
The expert persona lives in the `system` channel (sticky across the call), and only the data lives in `user` (the variable per-issue content):

**System** — *"You are an expert customer support triage agent... return ONLY the matching slug, never explanations..."*
**User** — *"Available playbook entries: [catalog]. Customer description: [text]. Return the slug or 'none'."*

This matters because LLMs treat system prompts as identity, not as instruction. The persona "leans in" harder, and (with prompt caching enabled) the same system prompt can be reused across thousands of calls at ~10% the input cost.

### Why I didn't use tool-use or RAG
The playbook is 10 entries (~500 tokens). Stuff-the-context wins on simplicity, latency, and cost at this scale. **Tool-use** (LLM calling our API to look things up mid-conversation) is the right answer when the LLM needs to *act* on the world, not when it needs to *read* a small dataset. **RAG** (retrieve top-k relevant entries before sending to the LLM) starts paying for itself around ~50+ entries. We're not there.

This is documented as the migration path: if the playbook grows, RAG is the next step before tool-use.

---

## Testing

`php artisan test`

```
17 tests, 63 assertions, ~210ms
✓ Issue API — 12 tests covering create/list/show/update/validation/XSS/escalation/optimistic-locking
✓ Playbook API — 5 tests covering index/show/triggers visibility/404
```

Tests use in-memory SQLite (`:memory:`) for speed and re-seed Categories + Playbook in `setUp()` for isolation. `phpunit.xml` forces the LLM env vars empty so tests can't accidentally call the real Gemini API.

---

## What I'd improve with more time

In rough priority order:

1. **Move the LLM call into a queued job.** Right now it's synchronous on POST — adds 1-2s latency when escalation kicks in. A queued worker with retries would let the API respond in ~50ms and backfill the LLM-derived summary asynchronously. Issue resource would expose `summary: null` until the job lands.
2. **Migrate the KB from "stuff-the-context" to RAG** if the playbook ever grows past ~50 entries. Vector embeddings (OpenAI's `text-embedding-3-small` is fine), pgvector for storage, top-3 retrieval before sending to the LLM. Documented above as the architectural off-ramp.
3. **Admin endpoints for priority overrides + KB editing.** Currently both are admin-by-omission (no public write path). With auth in scope, I'd add `PATCH /api/admin/issues/{id}/priority` and full CRUD on `/api/admin/playbook`.
4. **Prompt caching on the LLM provider side.** Anthropic and Gemini both support it — the system prompt + KB catalog are identical across calls and could cut input cost ~90%.
5. **De-escalation workflow** when an issue's conditions resolve. Right now `escalated_at` is sticky by design. With more time I'd add a separate `de_escalated_at` audit column and an admin action.
6. **Move strict-validation onto the `escalated` query param** — currently `?escalated=garbage` silently treats it as `false` (PHP's `FILTER_VALIDATE_BOOLEAN` semantics). A typo passes silently. Worth adding an `in:true,false,1,0,yes,no` rule.
7. **Replace JSON columns on `playbook_entries` with normalized child tables** (`playbook_faqs`, `playbook_steps`) once anything needs to query, tag, or version individual FAQs.
8. **Soft-deletes on Issue** — preserves data when issues are removed and matches typical ticketing-system behavior.
9. **Pest instead of PHPUnit** for more readable test files. Pure quality-of-life.
10. **Separate the frontend repo from the backend.** Combined here for assessment simplicity. In production I'd ship a standalone Laravel API and a separate Vue SPA repo deployed to Cloudflare Pages / Vercel / S3, with proper CORS, environment-coordinated API URLs, and independent deploy pipelines. Easier scaling, cleaner ownership, no risk of front-end build artifacts shipping with backend deploys.
11. **Frontend tests.** Currently only the backend has 17 PHPUnit tests. Vitest + Vue Test Utils for component tests; Playwright for end-to-end flows (submit → see in table → update status → modal closes).

---

## Pointers for navigation

- **Granular phase-by-phase build log** with every decision and trade-off → [`0-docu/TODO.md`](0-docu/TODO.md)
- **Working agreement** (modular layout rules, model-tier strategy, autonomy & critical-action rules) → [`CLAUDE.md`](CLAUDE.md)
- **Original assessment spec** → [`0-docu/Software Developer Practical Assessment.pdf`](0-docu/Software%20Developer%20Practical%20Assessment.pdf)

---

## Final notes

This was deliberately built as if it were a real intake system, not a CRUD demo. The escalation rule, the Knowledge Base, the rules-first LLM pattern, the multi-provider abstraction, the optimistic locking, the structured logging, the SPA UI on top of a clean REST API — none of those were minimally required by the spec. They're there because that's how a senior support tool should behave.

What's missing is intentional: no auth (out of scope per the brief), no production deployment story (would have been hand-waving), no separated frontend repo (combined for simplicity, would split in production).

If you want to see the brain on paper — every tradeoff, every "should we do X or Y", every time something broke and got fixed — it's all in [`0-docu/TODO.md`](0-docu/TODO.md). That doc is the honest record.
