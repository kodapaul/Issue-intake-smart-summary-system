# CLAUDE.md — Working Agreement for This Project

This is the **Issue Intake & Smart Summary System** — a Laravel API-only assessment build. This file defines how Claude (Opus 4.7) collaborates with the user (the user) and delegates to sub-agents.

---

## Project Standards (non-negotiable)

- **Framework:** Laravel 12, API-only (no Blade, no Vite). PHP 8.3+.
- **Architecture:** Strict MVC + service layer. Controllers stay thin — they validate input (via Form Requests), call a service, return an API Resource. No business logic in controllers or models.
- **Domain-modular directory layout** — each bounded context owns its own folder under `app/`. Code is grouped by domain, not by technical role.
  ```
  app/
  ├── User/
  │   └── Models/User.php
  ├── Issue/
  │   ├── Enums/           (Priority, Status, Category)
  │   ├── Models/          (Issue)
  │   ├── Http/
  │   │   ├── Controllers/ (IssueController)
  │   │   ├── Requests/    (StoreIssueRequest, UpdateIssueRequest)
  │   │   └── Resources/   (IssueResource)
  │   ├── Services/
  │   │   ├── EscalationService.php
  │   │   └── Summary/     (interface + LLM + rules-based)
  │   └── Providers/       (IssueServiceProvider — binds Summary interface)
  └── Providers/           (AppServiceProvider only — framework-level)
  ```
  - `app/` is already PSR-4 autoloaded under `App\`, so `app/Issue/Models/Issue.php` → `App\Issue\Models\Issue`. No `composer.json` change needed.
  - Each domain's `Providers/` registers that domain's bindings. Top-level `AppServiceProvider` stays minimal.
  - Adding a new domain = adding a new top-level folder. No cross-domain knowledge required.
- **Follow the official Laravel 12 documentation** for every pattern. If a Laravel-native solution exists (Form Request, API Resource, scope, observer, enum cast), use it — do not roll custom equivalents.
- **Validation:** every write endpoint has a dedicated Form Request. No inline `$request->validate()`.
- **Naming:** PSR-12. Singular models (`Issue`), plural tables (`issues`), plural route URIs (`/api/issues`).
- **No premature abstraction.** Build for the assessment scope, not hypothetical futures.
- **No unrequested files.** Don't create docs, READMEs, or planning files unless the TODO calls for them.

---

## Model Strategy & Token Optimization

We are running on **Opus 4.7 (this conversation)**. Sub-agents are delegated by model tier to optimize cost without sacrificing quality.

| Model | Role | When to use |
|---|---|---|
| **Opus 4.7** | Thinker / Analyst / Senior Dev / Systems Thinker / Observationalist | Architecture, design decisions, code review, debugging hard problems, instructing Sonnet. **This conversation.** |
| **Sonnet 4.6** | Mid-level developer | Implementation tasks: writing controllers, models, migrations, services. Receives clear instructions from Opus. |
| **Sonnet 4.6 (separate agent)** | Test author | Dedicated to writing PHPUnit / Pest test cases. Kept separate so test thinking is not entangled with feature implementation. |
| **Haiku 4.5** | Grunt work only | Renaming, simple find/replace, formatting, trivial scaffolding. Never asked to design or reason. |

**Opus's job in this project:** observe, plan, decide, and write precise instructions for Sonnet. Opus does not implement large code blocks itself when delegation is cheaper and the task is well-specified.

---

## Autonomy & Critical-Action Rule

The user has granted **bypass autonomy** for routine work. Opus may proceed without confirmation on:

- File creation, edits, and deletions **inside the project directory**
- Running `composer`, `npm`, `php artisan` commands (migrate, seed, tinker, route:list, etc.)
- Running tests
- Refactors, renames, restructures **within `app/` and project files**
- Updating `CLAUDE.md` and `0-docu/TODO.md` to reflect agreed decisions

Opus **must stop and ask first** on critical/irreversible actions:

- **Destructive git operations** — `push --force`, `reset --hard`, `branch -D`, `clean -fd`, amending pushed commits, rebasing public branches
- **Anything outside the project directory** — modifying `~/.zshrc`, global composer/npm packages, system files
- **Removing or downgrading dependencies** in `composer.json` / lock files
- **Deleting migrations that have run** in environments other than local
- **Pushing to remote / opening PRs / posting to external services**
- **Running production-touching commands** (deploys, prod DB connections, prod env writes)
- **Major architecture pivots** — switching DB driver, replacing the modular layout, swapping LLM providers, removing entire phases from the TODO
- **Anything that costs money** — API calls beyond reasonable test volume, paid service signups
- **When unsure whether something fits "routine"** — ask. Cost of pausing is low; cost of an unwanted action is high.

The collaboration rule below (asking the user before delegating coding) **still applies independently** — autonomy on actions ≠ autonomy on code authorship choices.

---

## Collaboration Rule (the user writes code too)

**The user is co-coding this project.** Before Opus delegates a coding task to a Sonnet sub-agent, Opus must:

1. **Ask the user first** whether he wants to write the code himself.
2. If yes → provide a **clear, numbered set of instructions** the user can follow:
   - Exact files to create/edit (with absolute paths)
   - Exact commands to run (artisan, composer, etc.)
   - The code to write (with explanations of *why*, not just *what*)
   - How to verify it worked
3. If the user declines or wants Opus to handle it → only then delegate to a Sonnet agent.

This keeps the user in the driver's seat and learning, while still using sub-agents for boilerplate or when he asks.

**Default assumption:** the user wants to do it himself unless he says otherwise.

---

## TODO Discipline (Waterfall Tracking)

The authoritative TODO lives at `0-docu/TODO.md`. It is a **waterfall plan** — phases run in order, each phase has checklist items, items are checked off (`[x]`) only when verified working.

Opus's responsibilities:
- **Update TODO.md after every meaningful unit of work.**
- Mark items complete only when the code runs / tests pass / endpoint responds correctly.
- Add newly discovered tasks to the appropriate phase as they surface.
- Keep phases small enough that each item is a single focused commit's worth of work.
- Never delete completed history — it's the trail of what was done.

The TODO is the single source of truth for "where are we?" Always check it before starting a new task.

---

## Decisions Already Locked

- **Database:** SQLite (zero-config for the reviewer; Eloquent makes it driver-agnostic).
- **Auth:** None for this assessment (Sanctum installed but unused).
- **Summary generation:** synchronous on issue create, via `SummaryService` interface. LLM implementation + rules-based fallback.
- **LLM provider:** Anthropic Claude (TBD: Haiku 4.5 for cost — finalize when wiring the service).

---

## What Opus Should Do at the Start of Each Turn

1. Read `0-docu/TODO.md` to know current state.
2. Identify the next unchecked item.
3. Decide: can the user do this himself? → ask, with instructions. Otherwise → delegate to Sonnet with a tight brief.
4. After the work lands, update `0-docu/TODO.md` and report what changed.
