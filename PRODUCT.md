# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Managers and employees at Agility PR Solutions (a Media Insights Group company),
a work-from-home PR/media analytics team of roughly 30 people. Three access roles:
`administrator`, `manager`, `employee`. Employees log time against tasks and view
their own capacity standing; managers review team workload, capacity, and
optimization recommendations across their accounts; administrators configure
calibration settings (complexity weights, thresholds, tier boundaries).

## Product Purpose

A non-invasive decision-support platform for WFH teams: capacity forecasting
(logged hours vs. a threshold derived from baseline hours, leave, and a
target utilization per designation), workload management (task complexity
scoring and over-allocation warnings), and heuristic process optimization
(bottleneck detection that proposes task-redistribution recommendations for
a manager to accept or reject). Built as an MIT capstone project (PLMun
Master in Information Technology); the beneficiary is Media Insights Group /
Agility PR Solutions.

## Positioning

Deliberately **not** a surveillance/monitoring tool: no screenshots,
keystroke capture, app/website tracking, idle detection, webcam, or
location — compliance with RA 11165 (Philippine WFH/telecommuting law) is a
first-class product claim. Deliberately **not** an ML/predictive system:
every capacity and optimization number is a documented deterministic
formula (arithmetic + threshold comparison), not a model, so calculations
are auditable and explainable to end users and to the capstone panel. The
optimization engine only ever proposes; a human manager must explicitly
accept a recommendation before any task reassignment happens.

## Operating Context

Team members work from home and log time against tasks (via timer or manual
entry, including bulk import from a production-sheet CSV/Excel template).
Monthly and quarterly capacity reviews roll up into an "at-a-glance"
dashboard. Managers periodically review a queue of system-generated
redistribution recommendations. Administrators tune calibration settings
(complexity weights, workload threshold, performance tier boundaries,
bottleneck variance %) rather than having them hardcoded. The system runs
on a monthly cadence tied to `monthly_baselines` (standard hours, leave)
per calendar month.

## Capabilities and Constraints

- Server-rendered only: Blade + Livewire 3 + Alpine.js, Tailwind (Vite),
  Chart.js for charts. No REST API, no SPA, no websockets.
- Stack: Laravel 12 / PHP 8.2+, MySQL 8, Laravel Breeze (Blade+Livewire) auth,
  barryvdh/laravel-dompdf for PDF, maatwebsite/excel for CSV/Excel import,
  Pest for tests, `database` queue driver, cron-driven scheduler.
- No Docker/Kubernetes/microservices/Redis/Kafka/Elasticsearch/message brokers.
- Three fixed roles via a `role_id` column + Gates + Policies (no
  spatie/laravel-permission).
- Strict data-visibility constraint: an employee can never read another
  employee's raw time logs, enforced at middleware, policy, and query-scope
  layers — this is both a security requirement and a UX requirement (nothing
  in the UI may leak another employee's logs, even inadvertently).
- Domain vocabulary is fixed and must be used verbatim in UI copy: H_base,
  H_leave, H_poss, U_target, H_thresh, H_prod, Monthly/Quarterly Performance %,
  Performance Tier (below/acceptable/over), Effective Availability, Complexity
  Tier (small/medium/large), Complexity Weight, Workload Score, Designation
  (job title, carries U_target) vs. Role (access control only).
- All threshold/weight values are configurable via a `settings` table, never
  hardcoded — UI for administrators must expose these as editable calibration
  values, not fixed copy.

## Brand Commitments

Working product name: "AI-Driven Work OS" — used as a placeholder title,
not a finalized product/marketing brand. No logo, brand colors, or other
visual assets exist yet; the visual identity is open for this project to
establish.

## Evidence on Hand

None. No existing brand assets, screenshots, or reference UI from Media
Insights Group / Agility PR Solutions were provided. A sample
production-sheet CSV template exists at `docs/samples/production-sheet-template.csv`
for the time-tracking import feature (data shape only, not a visual reference).

## Product Principles

- Auditability over automation: every number a user sees must trace back to
  a visible, explainable formula — never present a value as if a model
  produced it.
- Human-in-the-loop by design: the system recommends, a manager decides;
  no UI pattern should imply auto-assignment or make acceptance feel like
  a formality.
- Privacy as a visible property, not just a backend rule: the interface
  itself should never expose one employee's data to another, and should
  read as intentionally restrained rather than as a monitoring dashboard.
- Boring, legible over clever: this is evaluated by an academic panel and
  used daily by a real WFH team — clarity and scanability outrank visual
  novelty in Operate surfaces.
- Configuration over hardcoding: anywhere a threshold, weight, or boundary
  appears in the UI, it must read as administrator-editable, not fixed.

## Accessibility & Inclusion

No formal conformance standard (e.g. WCAG) is mandated. Follow sensible
defaults (keyboard navigability, adequate color contrast, readable type)
appropriate for an internal tool used daily by a known set of ~30 users.
