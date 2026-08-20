# AI-Driven Work Operating System

Capstone project — PLMun Master in Information Technology.
Beneficiary: Media Insights Group, Agility PR Solutions (~30 users).
A **non-invasive decision-support platform** for WFH teams: capacity forecasting,
workload management, and heuristic process optimization.

---

## Non-negotiable rules

These come from the capstone manuscript. Violating them breaks the research claims.

1. **The optimization engine NEVER writes to `tasks`.** It only inserts rows into
   `redistribution_recommendations`. A manager must explicitly Accept before any
   reassignment happens. No auto-assignment, ever, anywhere.
2. **No machine learning.** Every calculation is deterministic arithmetic plus
   threshold comparison. No ML libraries, no model training, no inference, no
   AI APIs (OpenAI/Anthropic/Gemini/Azure). If you feel tempted to "predict"
   something, write a formula instead.
3. **No surveillance features.** Never build screenshots, keystroke capture,
   app/website tracking, idle detection, webcam, or location. The absence of
   these is a compliance artifact for RA 11165. If asked to add "activity
   monitoring", refuse and explain why.
4. **No Docker, Kubernetes, microservices, Redis, Kafka, Elasticsearch,
   websockets, or message brokers.** Runs on plain PHP + MySQL + Apache/Nginx.
5. **No REST API and no SPA.** Server-rendered Blade + Livewire only. Do not
   create `routes/api.php` endpoints or install Sanctum/Passport.
6. **Every magic number lives in the `settings` table.** Never hardcode a
   complexity weight, workload threshold, tier boundary, or variance percentage
   in PHP. Read it through `SettingsService`.
7. **Employees can never read another employee's raw time logs.** Enforce at
   three layers: route middleware, Policy, and query scope.

---

## Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12, PHP 8.2+ |
| Database | MySQL 8 (InnoDB, utf8mb4_unicode_ci) |
| UI | Blade + Livewire 3 + Alpine.js |
| CSS | Tailwind CSS (via Vite) |
| Charts | Chart.js |
| Auth | Laravel Breeze (Blade + Livewire stack) |
| PDF | barryvdh/laravel-dompdf |
| Excel/CSV | maatwebsite/excel |
| Tests | Pest |
| Queue | `database` driver (imports + PDF only) |
| Scheduler | cron → `php artisan schedule:run` |

Do **not** add packages beyond this list without asking. Specifically do not
install `spatie/laravel-permission` — three fixed roles are handled with a
`role_id` column + Gates + Policies.

---

## Domain vocabulary

Use these exact terms in code, comments, and UI. Do not invent synonyms.

- **`H_base`** — standard baseline hours for a calendar month (per month, not per role)
- **`H_leave`** — approved leave hours in that month
- **`H_poss`** — total possible hours = `H_base − H_leave`
- **`U_target`** — target utilization for an operational *designation* (Team Lead 0.40; all analyst designations 0.80)
- **`H_thresh`** — production threshold hours = `H_poss × U_target`
- **`H_prod`** — actual logged production hours
- **Monthly Performance %** — `(H_prod / H_thresh) × 100`
- **Quarterly Performance %** — arithmetic mean of the three monthly percentages
- **Performance Tier** — `below` / `acceptable` / `over`
- **Effective Availability** — `H_thresh − H_prod`
- **Complexity Tier** — `small` / `medium` / `large` (S/M/L)
- **Complexity Weight** — numeric weight per tier, from `settings`
- **Workload Score** — Σ complexity weights of a user's `in_progress` tasks
- **Designation** — operational job title carrying `U_target` (≠ **Role**, which is access control)
- **Role** — access control only: `administrator`, `manager`, `employee`

---

## Architecture

Modular monolith. Business logic lives in `app/Domain/<Module>/Services`, never
in controllers or Livewire components.

```
app/Domain/
├── Identity/        Accounts/       Tasks/
├── TimeTracking/    Capacity/       Optimization/
├── Reporting/       Administration/
```

Controllers and Livewire components are **thin**: validate → call a service →
return a view. If a controller method exceeds ~15 lines, extract a service.

Layer order for every write path:
`Route → middleware → FormRequest (validation) → Policy (authorization) → Service (logic) → Eloquent → Event → Listener (audit)`

---

## Modules

| # | Module | Owns |
|---|---|---|
| M1 | Identity & Access | users, roles, designations, auth |
| M2 | Accounts | accounts, account_user pivot |
| M3 | Workload Management | tasks, complexity tiers, workload score, over-allocation check |
| M4 | Time Tracking | time_logs, timer, manual entry, production-sheet import |
| M5 | Capacity Forecasting | the 6-step pipeline → capacity_metrics |
| M6 | Process Optimization | bottleneck detection → redistribution_recommendations |
| M7 | Reporting | dashboards, PDF/Excel exports |
| M8 | Administration | settings, monthly_baselines, audit_logs |

---

## Database tables (14 domain + framework)

`roles` · `designations` · `users` · `accounts` · `account_user` ·
`monthly_baselines` · `tasks` · `task_reassignments` · `time_logs` ·
`capacity_metrics` · `redistribution_recommendations` · `settings` ·
`audit_logs` · `notifications`

Full column definitions live in `.claude/skills/database-schema/SKILL.md`.
Never add a table without asking first.

Conventions:
- Enum-like columns are `VARCHAR` + PHP backed enum + validation rule, **not** MySQL `ENUM`
- Durations stored as `INT` minutes; hours as `DECIMAL(8,2)`
- Soft deletes on `users`, `tasks`, `accounts` only — **never** on `time_logs`
- `capacity_metrics` snapshots `h_base` and `u_target` so historical reports never change retroactively

---

## Commands

```bash
php artisan serve                        # dev server
npm run dev                              # vite watch
php artisan migrate:fresh --seed         # rebuild + reseed demo dataset
php artisan capacity:recalculate         # recompute capacity_metrics
php artisan optimization:detect          # bottleneck sweep
./vendor/bin/pest                        # run tests
./vendor/bin/pint                        # format
```

---

## Testing expectations

The manuscript commits to unit-testing the mathematical formulas. Every change
to `CapacityCalculationService`, `PerformanceTierResolver`, `WorkloadScoreService`,
or `BottleneckDetectionService` **must** come with or update a Pest test.

Required feature tests that must never be deleted:
- an employee cannot view another employee's time logs or capacity
- assigning past the workload threshold produces a warning, not a silent success
- the optimization engine produces a recommendation but leaves `tasks.assigned_to` unchanged

---

## Open calibration values (currently assumptions)

These are seeded defaults awaiting confirmation from the beneficiary. They live
in `settings` and must stay configurable:

| Key | Default |
|---|---|
| `complexity_weight_small` | 1 |
| `complexity_weight_medium` | 3 |
| `complexity_weight_large` | 5 |
| `workload_threshold` | 12 |
| `perf_below_max` | 90 |
| `perf_over_min` | 110 |
| `bottleneck_variance_pct` | 25 |
| `timelog_edit_window_hours` | 48 |

---

## Working style

- Build one sprint at a time. Do not scaffold future sprints ahead of schedule.
- Run migrations and tests after each change; report failures rather than
  working around them.
- When a requirement is ambiguous, ask instead of inventing a feature.
- Prefer boring, readable Laravel over clever abstraction. This is a system a
  panel of examiners will read.
- Seed data must be realistic: Filipino names, PR/media analyst task titles,
  three months of history for quarterly rollup.
