---
name: database-schema
description: Authoritative MySQL schema for the AI-Driven Work OS — every table, column, type, key, index, and seed value. Use when writing or editing any migration, model, factory, seeder, Eloquent relationship, or query that touches roles, designations, users, accounts, account_user, monthly_baselines, tasks, task_reassignments, time_logs, capacity_metrics, redistribution_recommendations, settings, or audit_logs.
---

# Database Schema

MySQL 8, InnoDB, `utf8mb4_unicode_ci`. 14 domain tables. **Do not add a table
without asking the user first.**

## Conventions

- Enum-like columns: `VARCHAR` + PHP backed enum + `Rule::enum()` validation.
  Never MySQL `ENUM` (an `ALTER TABLE` to add a value is worse than a validation change).
- Durations: `INT UNSIGNED` minutes. Hours: `DECIMAL(8,2)`. Percentages: `DECIMAL(6,2)`.
- Utilization: `DECIMAL(4,3)` (`0.400`, `0.800`).
- Soft deletes on `users`, `tasks`, `accounts` only. **Never** on `time_logs`.
- Foreign keys: `restrictOnDelete()` for master data, `cascadeOnDelete()` for pivots
  and child records that are meaningless alone.
- Every model declares explicit `$fillable`. Never `$guarded = []`.
- Every model declares `$casts` for dates, decimals, booleans, and enums.

---

## roles

3 rows, seeded. Access control only — **not** job titles.

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| name | varchar(50) unique | `administrator`, `manager`, `employee` |
| label | varchar(80) | display name |
| description | varchar(255) nullable | |
| timestamps | | |

## designations

5 rows, seeded. Operational job titles carrying `U_target`.

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| name | varchar(80) unique | |
| utilization_target | decimal(4,3) | |
| is_active | boolean default true | |
| timestamps | | |

Seed: Team Lead `0.400`; Sr. Reports Analyst, Reports Analyst, Toning Analyst,
Jr. Reports Analyst all `0.800`.

## users

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| employee_code | varchar(30) unique nullable | matches production-sheet identifier |
| first_name | varchar(80) | |
| last_name | varchar(80) | |
| email | varchar(150) unique | login |
| password | varchar(255) | hashed |
| role_id | FK roles restrictOnDelete | |
| designation_id | FK designations nullable restrictOnDelete | |
| manager_id | FK users nullable nullOnDelete | self-reference; scopes "own team" |
| is_active | boolean default true | |
| email_verified_at, remember_token | | Laravel defaults |
| softDeletes, timestamps | | |

Indexes: `role_id`, `designation_id`, `manager_id`, `is_active`.
Accessor: `full_name`.

## accounts

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| name | varchar(150) | client account |
| code | varchar(30) unique | |
| expected_monthly_hours | decimal(8,2) nullable | |
| is_active | boolean default true | |
| softDeletes, timestamps | | |

## account_user (pivot)

`id`, `account_id` FK cascade, `user_id` FK cascade, `assigned_at` datetime nullable, timestamps.
Unique `(account_id, user_id)`. Index `user_id`.

## monthly_baselines

Holds `H_base`. Per calendar month, **not** per role.

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| period_year | smallInteger | |
| period_month | tinyInteger | 1–12 |
| baseline_hours | decimal(8,2) | `H_base` |
| set_by | FK users nullable nullOnDelete | |
| timestamps | | |

Unique `(period_year, period_month)`.

## tasks

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| reference | varchar(20) unique | `TSK-2026-0001` |
| title | varchar(200) | |
| description | text nullable | |
| account_id | FK accounts restrictOnDelete | |
| assigned_to | FK users nullable restrictOnDelete | |
| created_by | FK users restrictOnDelete | |
| complexity_tier | varchar(10) | `small` / `medium` / `large` |
| complexity_weight | tinyInteger unsigned | snapshot at creation |
| standard_hours | decimal(8,2) | manager's expected effort |
| actual_hours | decimal(8,2) default 0 | cache of summed production logs |
| status | varchar(20) default `pending` | `pending`/`in_progress`/`completed`/`cancelled` |
| is_bottleneck | boolean default false | set by M6 |
| due_date | date nullable | |
| started_at, completed_at | datetime nullable | |
| softDeletes, timestamps | | |

Indexes: `(assigned_to, status)` ← live workload score;
`(complexity_tier, status)` ← historical average lookup;
`account_id`; `due_date`; `is_bottleneck`.

## task_reassignments

Proof that every reassignment was human-approved.

`id`, `task_id` FK cascade, `from_user_id` FK users nullable, `to_user_id` FK users,
`recommendation_id` FK redistribution_recommendations nullable nullOnDelete,
`reason` varchar(255) nullable, `performed_by` FK users, `created_at`.

## time_logs

The hottest table. **No soft deletes** — history integrity.

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| user_id | FK users restrictOnDelete | |
| task_id | FK tasks nullable nullOnDelete | null for non-production/leave |
| account_id | FK accounts nullable restrictOnDelete | time is logged per account |
| log_date | date | |
| hour_type | varchar(20) | `production` / `non_production` / `leave` |
| duration_minutes | integer unsigned | |
| entry_method | varchar(20) | `timer` / `manual` / `import` |
| started_at, ended_at | datetime nullable | populated by timer |
| notes | varchar(255) nullable | |
| is_locked | boolean default false | set once the period is computed |
| timestamps | | |

Indexes: `(user_id, log_date)`; `(user_id, hour_type, log_date)`; `task_id`;
`(account_id, log_date)`.
Validation: `duration_minutes` between 1 and 1440; `log_date` not in the future.

## capacity_metrics

Materialized results of the six-step pipeline.

| Column | Type |
|---|---|
| id | bigIncrements |
| user_id | FK users cascadeOnDelete |
| period_type | varchar(10) — `monthly` / `quarterly` |
| period_year | smallInteger |
| period_month | tinyInteger nullable |
| period_quarter | tinyInteger nullable |
| h_base | decimal(8,2) — snapshot |
| h_leave | decimal(8,2) |
| h_poss | decimal(8,2) |
| u_target | decimal(4,3) — snapshot |
| h_thresh | decimal(8,2) |
| h_prod | decimal(8,2) |
| h_non_prod | decimal(8,2) |
| performance_percentage | decimal(6,2) nullable |
| performance_tier | varchar(20) — `below`/`acceptable`/`over`/`not_applicable` |
| effective_availability_hours | decimal(8,2) |
| computed_at | datetime |
| timestamps | |

Unique `(user_id, period_type, period_year, period_month, period_quarter)` → safe `upsert()`.
Index `(period_type, period_year, period_month)`.

## redistribution_recommendations

| Column | Type |
|---|---|
| id | bigIncrements |
| task_id | FK tasks cascadeOnDelete |
| from_user_id | FK users restrictOnDelete |
| suggested_user_id | FK users nullable restrictOnDelete |
| trigger_type | varchar(30) — `bottleneck` / `over_allocation` |
| actual_hours | decimal(8,2) |
| historical_avg_hours | decimal(8,2) nullable |
| variance_percentage | decimal(6,2) nullable |
| from_workload_score | smallInteger |
| suggested_workload_score | smallInteger nullable |
| basis | varchar(30) — `account_history`/`tier_history`/`standard_hours` |
| reason | varchar(500) |
| status | varchar(20) default `pending` — `pending`/`accepted`/`dismissed` |
| reviewed_by | FK users nullable nullOnDelete |
| reviewed_at | datetime nullable |
| timestamps | |

Index `(status, created_at)`, `task_id`.
Prevent duplicates: don't create a new `pending` row for a task that already has one.

## settings

`id`, `key` varchar(100) unique, `value` varchar(255), `type` varchar(20)
(`int`/`decimal`/`bool`/`string`), `group` varchar(50), `label` varchar(150),
`updated_by` FK users nullable, timestamps.

Seeded keys and defaults:

| key | value | type | group |
|---|---|---|---|
| complexity_weight_small | 1 | int | workload |
| complexity_weight_medium | 3 | int | workload |
| complexity_weight_large | 5 | int | workload |
| workload_threshold | 12 | int | workload |
| perf_below_max | 90 | decimal | capacity |
| perf_over_min | 110 | decimal | capacity |
| bottleneck_variance_pct | 25 | decimal | optimization |
| timelog_edit_window_hours | 48 | int | timetracking |

Access only through `SettingsService` (cached per request). Never `Setting::where('key', ...)` inline.

## audit_logs

`id`, `user_id` FK nullable nullOnDelete, `action` varchar(80),
`auditable_type` varchar(120) nullable, `auditable_id` bigInteger nullable,
`description` varchar(255) nullable, `ip_address` varchar(45) nullable,
`user_agent` varchar(255) nullable, `created_at`.

Index `(auditable_type, auditable_id)`, `user_id`, `created_at`.

Logged actions: login, logout, failed_login, user_created, user_updated,
role_changed, task_created, task_assigned, task_reassigned,
over_allocation_override, setting_updated, baseline_updated,
recommendation_accepted, recommendation_dismissed, production_sheet_imported.

---

## Relationships (Eloquent)

```php
User: belongsTo Role, Designation, User(manager); hasMany User(subordinates),
      Task(assignedTasks via assigned_to), Task(createdTasks via created_by),
      TimeLog, CapacityMetric; belongsToMany Account
Account: belongsToMany User; hasMany Task, TimeLog
Task: belongsTo Account, User(assignee), User(creator);
      hasMany TimeLog, TaskReassignment, RedistributionRecommendation
TimeLog: belongsTo User, Task, Account
CapacityMetric: belongsTo User
RedistributionRecommendation: belongsTo Task, User(from), User(suggested), User(reviewer)
```

## Required query scopes

```php
Task::scopeVisibleTo($q, User $u)     // employee → where assigned_to = $u->id
TimeLog::scopeVisibleTo($q, User $u)  // employee → where user_id = $u->id
CapacityMetric::scopeVisibleTo($q, User $u)
Task::scopeActive($q)                 // status = 'in_progress'
```

Defence in depth: even a forgotten Policy check must not leak another
employee's raw logs.

## Seeder order

`Role → Designation → Setting → MonthlyBaseline → Account → DemoUser →
AccountUser → HistoricalDataset`

`HistoricalDatasetSeeder` must generate **three full months** of tasks and time
logs so the quarterly rollup has real data on `migrate:fresh --seed`. Deliberately
include: one over-allocated employee, one under-utilized employee, one task with
a >25% overrun to trigger a bottleneck. The demo depends on this.
