# User Acceptance Test Script

**System:** AI-Driven Work Operating System
**Beneficiary:** Media Insights Group, Agility PR Solutions
**Prerequisite:** `php artisan migrate:fresh --seed` completed successfully, using the
`HistoricalDatasetSeeder` demo dataset (3 client accounts, 1 administrator, 2 team
leads, 12 analysts, 3 months of history + current month). All demo accounts use
password `password`.

> Note: all previously-blocked pieces are now implemented and tested —
> `HistoricalDatasetSeeder` (running `migrate:fresh --seed` produces the full
> demo dataset, including at least one pending recommendation on first load),
> the bottleneck-detection engine (`BottleneckDetectionService`,
> `RedistributionRecommender`, `optimization:detect`), and the
> over-allocation-at-assignment warning (`AssignTaskModal`, scenario 6). No
> scenario in this script is blocked.

## How to use this script

- **Alpha testing** (IT experts, internal team) — run all scenarios, focusing on
  calculation accuracy (arithmetic must match a hand calculation exactly).
- **Beta testing** (end users at the beneficiary site) — run the scenarios
  marked **Beta**, focusing on usability, clarity of language, and whether the
  tester can complete the task without help.
- Record Pass/Fail for every step. A "Fail" requires a one-line note on what
  was observed instead of the expected result.
- Testers should not be told the "expected result" column in advance during
  Beta usability testing — only the task instruction.

---

## Module: Identity & Access (M1)

| # | Purpose | Scenario | Steps | Expected Result | Pass | Fail | Notes |
|---|---|---|---|---|---|---|---|
| 1 | Alpha/Beta | Login as each of the three roles | 1. Go to `/login`.<br>2. Log in as the seeded administrator.<br>3. Log out. Repeat for a manager account, then an employee account. | Each role lands on its own dashboard (`/admin/dashboard`, `/manager/dashboard`, `/my/dashboard`) and the sidebar shows only nav items permitted for that role. | ☐ | ☐ | |
| 2 | Alpha | Employee attempts to open a manager URL directly | 1. Log in as an employee.<br>2. Manually navigate to `/manager/dashboard` (or `/dashboard/monthly-at-a-glance`). | HTTP 403 Forbidden. No dashboard data is rendered. | ☐ | ☐ | |
| 2b | Alpha | Manager attempts to open an admin-only URL directly | 1. Log in as a manager.<br>2. Navigate to `/admin/accounts`. | HTTP 403 Forbidden. | ☐ | ☐ | |
| 3 | Alpha | Admin sets `U_target` for a designation | 1. Log in as administrator.<br>2. Open Designations settings.<br>3. Change an analyst designation's `U_target` from 0.80 to, e.g., 0.75. Save.<br>4. Run `php artisan capacity:recalculate` for the current month.<br>5. Open a prior month's already-computed metric for the same employee. | New value persists and is used for the current-month recalculation. The prior month's `capacity_metrics` row (its snapshotted `h_base`/`u_target`) is unchanged. | ☐ | ☐ | |

## Module: Accounts (M2)

| # | Purpose | Scenario | Steps | Expected Result | Pass | Fail | Notes |
|---|---|---|---|---|---|---|---|
| 4 | Alpha | Employee sees only assigned accounts | 1. Log in as an employee assigned to 1 of the 3 seeded accounts.<br>2. Open "My Accounts". | Only the assigned account is listed; the other two client accounts are not visible or reachable by direct URL. | ☐ | ☐ | |
| 5 | Beta | Manager views a client account's team | 1. Log in as a manager.<br>2. Open an account they manage.<br>3. Review the listed team members. | The account's assigned analysts and their designations display clearly. | ☐ | ☐ | |

## Module: Workload Management (M3)

| # | Purpose | Scenario | Steps | Expected Result | Pass | Fail | Notes |
|---|---|---|---|---|---|---|---|
| 6 | Alpha | Manager creates a Large task and assigns it to an at-threshold employee | 1. Log in as a manager.<br>2. Open an account and find an employee whose Workload Score is at/near `workload_threshold` (e.g. Faith Navarro or Nathaniel Flores in the seeded dataset).<br>3. In "Create & assign task", create a new Large-complexity task and assign it to that employee. | A warning appears showing the employee's current Workload Score, the prospective score after this task, the threshold, and up to 3 alternative employees on the account with more spare capacity (or a note that none exist). No task is created yet. Clicking "Assign anyway" creates the task and logs an `over_allocation_override` audit entry in addition to `task_assigned`; changing the assignee first clears the warning and re-checks. | ☐ | ☐ | |
| 7 | Beta | View team workload chart | 1. Log in as a manager.<br>2. Open the Team Workload chart. | Each employee's workload score bar reflects only `in_progress` task weights; colours match the tier legend described in the user guide. | ☐ | ☐ | |

## Module: Time Tracking (M4)

| # | Purpose | Scenario | Steps | Expected Result | Pass | Fail | Notes |
|---|---|---|---|---|---|---|---|
| 8 | Alpha/Beta | Employee starts and stops a timer | 1. Log in as an employee.<br>2. Select an account/task, choose hour type "Production".<br>3. Click Start. Wait ~2 minutes.<br>4. Click Stop. | A `time_logs` row is created with `entry_method = 'timer'` and a duration matching the elapsed wall-clock time (±1 minute). | ☐ | ☐ | |
| 9 | Alpha/Beta | Employee manually logs 8h leave | 1. Log in as an employee.<br>2. Open Manual Entry.<br>3. Log 8 hours, hour type "Leave", for today's date. Save.<br>4. Run `php artisan capacity:recalculate` for the current month.<br>5. View the employee's own capacity for the month. | `H_leave` for the month increases by 8; `H_poss` (`H_base − H_leave`) falls by 8; `H_thresh` (`H_poss × U_target`) falls accordingly. | ☐ | ☐ | |
| 10 | Alpha/Beta | Import a production sheet | 1. Log in as a manager.<br>2. Download the CSV template.<br>3. Fill in rows, including at least one row with a bad date and one duplicate row.<br>4. Upload the file. | The preview screen separates valid rows from rejected rows, each rejected row showing a human-readable reason (e.g. "duplicate of an existing time log", "invalid date format"). | ☐ | ☐ | |
| 11 | Alpha | Employee cannot view another employee's time logs | 1. Log in as Employee A.<br>2. Attempt to view Employee B's time-log detail via direct URL manipulation (change the `user_id` query param or path segment). | Request is denied (403) or returns only Employee A's own data — never Employee B's rows. | ☐ | ☐ | |
| 12 | Alpha | Production-sheet import restricted by role | 1. Log in as an employee.<br>2. Attempt to reach the production-sheet import page/route directly. | HTTP 403. Only managers/administrators can import. | ☐ | ☐ | |

## Module: Capacity Forecasting (M5)

| # | Purpose | Scenario | Steps | Expected Result | Pass | Fail | Notes |
|---|---|---|---|---|---|---|---|
| 13 | Alpha | Run `capacity:recalculate` | 1. Pick one seeded employee. By hand, compute `H_poss`, `H_thresh`, and Monthly Performance % from the seeded baseline and time logs.<br>2. Run `php artisan capacity:recalculate` for that month.<br>3. Open the Monthly At-a-Glance dashboard for that employee. | On-screen figures match the hand calculation exactly (to 2 decimal places). | ☐ | ☐ | |
| 14 | Alpha | Full-month leave produces no divide-by-zero | 1. Identify the seeded employee on full-month leave.<br>2. Run `capacity:recalculate`.<br>3. View their monthly metric. | No error/500. Percentage displays as "N/A" (Performance Tier `NotApplicable`), not a crash or a `0%`. | ☐ | ☐ | |
| 15 | Alpha | Missing baseline is refused, not guessed | 1. As administrator, ensure no `monthly_baselines` row exists for a future month.<br>2. Run `php artisan capacity:recalculate --month=<future>`. | Command fails with a clear error stating an administrator must configure `H_base` first. No metrics are silently fabricated. | ☐ | ☐ | |
| 16 | Alpha/Beta | Quarterly view | 1. Note the three monthly percentages for one employee across the seeded quarter.<br>2. Compute their arithmetic mean by hand.<br>3. Open the Quarterly At-a-Glance dashboard for that employee. | Displayed quarterly percentage equals the mean of the three monthly percentages (±0.01 due to rounding). | ☐ | ☐ | |
| 17 | Beta | Employee views own capacity | 1. Log in as an employee.<br>2. Open "My Dashboard". | Only the logged-in employee's own H_poss/H_thresh/H_prod/tier are shown; no teammate data is present or reachable from this page. | ☐ | ☐ | |
| 18 | Beta | Monthly drill-down shows full arithmetic | 1. Log in as manager.<br>2. Open Monthly At-a-Glance, click into one employee's row. | The drill-down displays every intermediate figure (`H_base`, `H_leave`, `H_poss`, `U_target`, `H_thresh`, `H_prod`, %, tier) so the number can be hand-verified without opening the database. | ☐ | ☐ | |

## Module: Process Optimization (M6)

| # | Purpose | Scenario | Steps | Expected Result | Pass | Fail | Notes |
|---|---|---|---|---|---|---|---|
| 19 | Alpha | Overrun task triggers detection | 1. Identify or create an `in_progress` task whose logged hours exceed the account/tier's historical average by more than `bottleneck_variance_pct` (25%).<br>2. Run the bottleneck sweep: `php artisan optimization:detect`. | A new row appears in the recommendation queue referencing the overrun task, with a plain-language reason citing the actual hours, historical average, and a suggested assignee (or a note that none is available). `tasks.assigned_to` is unchanged. Re-running the command does not create a duplicate. | ☐ | ☐ | |
| 20 | Alpha/Beta | Manager dismisses a recommendation | 1. Log in as manager.<br>2. Open the Recommendation Queue.<br>3. Dismiss a pending recommendation (with a reason if prompted). | Recommendation status becomes `dismissed`. No `task_reassignments` row is created; `tasks.assigned_to` is unchanged. | ☐ | ☐ | |
| 21 | Alpha/Beta | Manager accepts a recommendation | 1. Log in as manager.<br>2. Open the Recommendation Queue.<br>3. Accept a pending recommendation. | Exactly one `task_reassignments` row is written, `tasks.assigned_to` updates to the recommended employee, and exactly one audit-log entry is recorded for the action. | ☐ | ☐ | |
| 22 | Alpha | Employee cannot reach the recommendation queue | 1. Log in as employee.<br>2. Navigate to `/optimization/recommendations` directly. | HTTP 403. | ☐ | ☐ | |

## Module: Reporting (M7)

| # | Purpose | Scenario | Steps | Expected Result | Pass | Fail | Notes |
|---|---|---|---|---|---|---|---|
| 23 | Alpha | Export monthly PDF | 1. Log in as manager.<br>2. Open Monthly At-a-Glance for one employee; note the on-screen figures.<br>3. Queue and download the Monthly Capacity PDF for the same employee/month. | Every figure in the PDF (H_poss, H_thresh, H_prod, %, tier) matches the dashboard exactly. | ☐ | ☐ | |
| 24 | Beta | Export team workload report | 1. Log in as manager.<br>2. Queue the Team Workload PDF.<br>3. Wait for the completion notification. | A notification confirms the PDF is ready; the file downloads and lists all team members' workload scores. | ☐ | ☐ | |
| 25 | Alpha | Time-log export scoping | 1. Log in as employee.<br>2. Attempt to export a time-log report passing another user's ID as a filter (via URL manipulation, if the UI allows a parameter). | Export contains only the requesting employee's own logs, regardless of the filter passed. | ☐ | ☐ | |
| 26 | Alpha | Report access restricted by role | 1. Log in as employee.<br>2. Attempt to queue the Monthly Capacity PDF for another employee. | Action is forbidden (403 or authorization error), not silently ignored. | ☐ | ☐ | |

## Module: Administration (M8)

| # | Purpose | Scenario | Steps | Expected Result | Pass | Fail | Notes |
|---|---|---|---|---|---|---|---|
| 27 | Alpha | Settings changes are audited | 1. Log in as administrator.<br>2. Change `bottleneck_variance_pct` from 25 to 20. Save. | Value persists and is reflected immediately in `SettingsService`; an audit-log entry records the old and new value, the admin, and the timestamp. | ☐ | ☐ | |
| 28 | Beta | Admin configures a monthly baseline | 1. Log in as administrator.<br>2. Set `H_base` for next month. | Value saves without error and is available to `capacity:recalculate` when that month arrives. | ☐ | ☐ | |

---

## Usability (Beta only) — System Usability Scale style questions

Administer alongside the ISO/IEC 25010 instrument in `docs/evaluation-instrument.md`
after the Beta tester completes scenarios 1, 5, 7–10, 16–18, 20–21, 24, 28.

| # | Question | Rating (1–5) |
|---|---|---|
| B1 | I could complete the assigned task without asking for help. | |
| B2 | The tier colours (below/acceptable/over) were easy to interpret. | |
| B3 | The language used in warnings and confirmations was clear. | |
| B4 | I felt confident the system was not tracking anything beyond my logged hours. | |

## Sign-off

| Role | Name | Signature | Date |
|---|---|---|---|
| Alpha Tester (IT Expert) | | | |
| Alpha Tester (IT Expert) | | | |
| Beta Tester (End User) | | | |
| Project Adviser | | | |
