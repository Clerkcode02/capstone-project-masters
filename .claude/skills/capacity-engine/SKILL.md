---
name: capacity-engine
description: Canonical specification for the capacity forecasting, workload scoring, and process optimization calculations. Use whenever writing or modifying CapacityCalculationService, PerformanceTierResolver, WorkloadScoreService, BottleneckDetectionService, RedistributionRecommender, the capacity:recalculate or optimization:detect commands, or any test covering H_poss, H_thresh, performance percentage, quarterly aggregation, workload score, or bottleneck variance.
---

# Capacity & Optimization Engine

These formulas come directly from Chapter 3 of the capstone manuscript. They are
the research contribution — implement them **exactly**. Do not "improve" them,
smooth them, or substitute a statistical model.

## Rule zero

Everything here is deterministic arithmetic. No machine learning, no
probabilistic estimation, no external service. Every number must be reproducible
by hand on a whiteboard during the oral defense.

---

## 1. Capacity forecasting — the six steps

Implemented by `App\Domain\Capacity\Services\CapacityCalculationService`.

### Step 1 — Ingest monthly time logs

```sql
SELECT user_id, hour_type, SUM(duration_minutes) / 60 AS hours
FROM time_logs
WHERE log_date BETWEEN :month_start AND :month_end
GROUP BY user_id, hour_type
```

Yields three variables per user: `H_prod` (`hour_type = 'production'`),
`H_nonprod` (`non_production`), `H_leave` (`leave`).
Missing bucket → `0.00`, not null.

### Step 2 — Total possible hours

```
H_base  ← monthly_baselines WHERE period_year = Y AND period_month = M
H_poss  = H_base − H_leave
```

If no baseline row exists for the period, **abort with a clear exception**. Do
not guess 160. The admin must configure it.

### Step 3 — Production threshold hours

```
U_target ← designations.utilization_target of the user
H_thresh = H_poss × U_target
```

If the user has no designation, skip them and log a warning.

### Step 4 — Monthly performance percentage

```
IF H_thresh <= 0:  performance_tier = 'not_applicable', percentage = null
ELSE:              performance_percentage = (H_prod / H_thresh) × 100
```

The guard matters: an employee on full-month leave has `H_poss = 0`, so
`H_thresh = 0`. Never divide by zero and never render them as "Below".

### Step 5 — Tier mapping (Performance Categorization Scale)

Boundaries come from `settings`, never hardcoded.

```
pct <  perf_below_max (default 90)   → 'below'
pct <= perf_over_min  (default 110)  → 'acceptable'
pct >  perf_over_min                 → 'over'

effective_availability_hours = H_thresh − H_prod
```

Implemented by `PerformanceTierResolver` as a pure function so it is trivially
unit-testable and reusable by the quarterly step.

### Step 6 — Quarterly aggregation

```
quarterly_percentage = mean(month1_pct, month2_pct, month3_pct)
```

- Arithmetic mean of the **percentages**, not of the raw hours. This is what the
  manuscript specifies.
- Exclude months with `not_applicable` from the mean; if all three are excluded,
  the quarter is `not_applicable`.
- Feed the result back through the **same** Step 5 resolver.
- Quarterly rows also store summed `h_poss`, `h_thresh`, `h_prod` for reference.

### Persistence

Upsert into `capacity_metrics` keyed on
`(user_id, period_type, period_year, period_month, period_quarter)`.

**Snapshot `h_base` and `u_target` onto the row.** If an admin later changes the
80% target, last quarter's report must not silently change. This is an
auditability requirement, not an optimization.

### Triggers

- `php artisan capacity:recalculate {--year=} {--month=} {--user=}` — nightly cron at 01:00
- On-demand "Recalculate" button on the manager dashboard (needed for the live demo)
- After a production-sheet import commits, for the affected periods only

---

## 2. Workload scoring

Implemented by `App\Domain\Tasks\Services\WorkloadScoreService`.

```
workload_score(user) = SUM(tasks.complexity_weight)
                       WHERE assigned_to = user
                         AND status = 'in_progress'
                         AND deleted_at IS NULL
```

- Only `in_progress` counts. `pending` tasks are not yet consuming effort;
  `completed` and `cancelled` exit the score.
- Use `tasks.complexity_weight` — the value snapshotted at creation — not a live
  `settings` lookup. Historical scores must stay reproducible if weights change.

### Pre-assignment over-allocation check

Runs **before** a task is assigned, in `AssignTaskModal`:

```
current     = workload_score(candidate)
prospective = current + task.complexity_weight
threshold   = settings('workload_threshold')

IF prospective > threshold:
    render a blocking warning containing the real numbers and up to three
    alternative assignees ordered by (threshold − their score) DESC
    → manager may still proceed ("Assign anyway") — this is decision support,
      not enforcement — but the override is written to audit_logs
```

Never silently block and never silently allow. The warning **is** the feature.

---

## 3. Bottleneck detection

Implemented by `App\Domain\Optimization\Services\BottleneckDetectionService`.

```
actual = SUM(production minutes logged against the task) / 60

historical_avg = AVG(actual_hours)
    FROM tasks
    WHERE status = 'completed'
      AND complexity_tier = :tier
      AND account_id = :account
      AND actual_hours > 0

  fallback 1: same tier, any account   (when < 3 samples on the account)
  fallback 2: task.standard_hours      (cold start — no history at all)

variance_pct = ((actual − historical_avg) / historical_avg) × 100

IF variance_pct > settings('bottleneck_variance_pct'):
    tasks.is_bottleneck = true
```

Record which basis was used (`account_history` / `tier_history` /
`standard_hours`) in the recommendation `reason`. The cold-start fallback is a
documented limitation in the manuscript — surfacing it honestly is a strength,
not a bug.

---

## 4. Redistribution recommendation

Implemented by `App\Domain\Optimization\Services\RedistributionRecommender`.

Candidate filter — all conditions must hold:

```
- assigned to the same account as the task
- is_active = true
- user_id != current assignee
- workload_score < settings('workload_threshold')
- latest capacity_metrics.performance_tier != 'over'
```

Ranking:

```
ORDER BY (workload_threshold − workload_score) DESC,
         effective_availability_hours DESC
LIMIT 3
```

Persist to `redistribution_recommendations` with the full evidence set:
`actual_hours`, `historical_avg_hours`, `variance_percentage`,
`from_workload_score`, `suggested_workload_score`, and a plain-language `reason`:

> "This task has consumed 14.5h against a 9.0h historical average for Medium
> tasks on Account X (+61%). Maria Santos has 4 of 12 workload points and 22.0h
> of remaining capacity this month."

The self-explaining recommendation is exactly what makes a heuristic engine more
defensible than a black-box model. Never emit a bare score.

### The hard boundary

`RedistributionRecommender` and every service in `app/Domain/Optimization`
**must not** update `tasks.assigned_to`. Reassignment happens only in
`Manager\RecommendationController@accept`, which writes a `task_reassignments`
row, updates the task, and sets `recommendation.status = 'accepted'`.

There must be a feature test asserting that running the detection command leaves
every `tasks.assigned_to` value unchanged.

---

## 5. Precision and rounding

- Store durations as integer minutes; convert to hours only at calculation time
- Persist hours as `DECIMAL(8,2)`; percentages as `DECIMAL(6,2)`
- Round **only at persistence**, never mid-formula
- Use PHP `round($v, 2)`; never format-then-parse

## 6. Required Pest tests

| Test | Asserts |
|---|---|
| `H_poss` | `160 − 16 = 144.00` |
| `H_thresh` analyst | `144 × 0.80 = 115.20` |
| `H_thresh` team lead | `144 × 0.40 = 57.60` |
| Monthly % | `100.8 / 115.2 × 100 = 87.50` |
| Tier below | `87.50 → 'below'` |
| Tier acceptable | `100.00 → 'acceptable'`, `90.00 → 'acceptable'`, `110.00 → 'acceptable'` |
| Tier over | `110.01 → 'over'` |
| Full-month leave | `H_thresh = 0 → 'not_applicable'`, no division by zero |
| Quarterly mean | `mean(87.5, 102.0, 95.5) = 95.00 → 'acceptable'` |
| Quarterly with gap | one `not_applicable` month is excluded from the mean |
| Missing baseline | throws, does not default |
| Workload score | only `in_progress` tasks counted |
| Snapshot integrity | changing `u_target` afterwards does not alter a stored metric |
| Engine boundary | detection command leaves all `assigned_to` unchanged |
