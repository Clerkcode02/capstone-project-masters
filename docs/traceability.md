# Traceability Matrix

Maps every implemented feature to the manuscript chapter/section that
justifies it, the class that implements it, and the test that verifies it.
Per the working rule in `CLAUDE.md`: if a row has no manuscript location, the
feature should not have been built.

Status legend: **Done** = implemented and tested. **Partial** = implemented
but test coverage is incomplete. **Gap** = specified in the manuscript /
CLAUDE.md but not yet implemented — see "Known gaps" at the end.

## M1 — Identity & Access

| Requirement | Manuscript location | Implementation | Test | Status |
|---|---|---|---|---|
| Three fixed roles (administrator/manager/employee), no package-based RBAC | Ch. 2, System Design — Access Control | `App\Domain\Identity\Enums\Role`, `role_id` column on `users`, `EnsureUserHasRole` middleware | `RoleAuthorizationTest` | Done |
| Route-level role gating | Ch. 3, Non-functional Requirements — Security | `app/Http/Middleware/EnsureUserHasRole.php`, `role:` middleware groups in `routes/web.php` | `RoleAuthorizationTest`, `MonthlyAtAGlanceTest` (denies employees), `QuarterlyAtAGlanceTest`, `TeamWorkloadChartTest` | Done |
| Policy-level authorization per model | Ch. 3, Architecture — Layer order | `app/Policies/*.php` (`UserPolicy`, `TaskPolicy`, `TimeLogPolicy`, `CapacityMetricPolicy`, `RecommendationPolicy`, `AuditLogPolicy`), registered in `AuthServiceProvider` | `TimeLogAuthorizationTest`, `ReportAuthorizationTest` | Done |
| Audit trail of auth events (login/logout/failed login) | Ch. 3, M8 Administration — audit_logs | `App\Listeners\WriteAuditLog` (`handleLogin`, `handleLogout`, `handleFailedLogin`), `audit_logs` table | `AuditLoggingTest` | Done |
| Role change / user create / update audited | Ch. 3, M8 Administration | `WriteAuditLog::handleUserCreated/handleUserUpdated/handleRoleChanged`, `App\Events\UserCreated`, `UserUpdated`, `RoleChanged` | `AuditLoggingTest` | Done |

## M2 — Accounts

| Requirement | Manuscript location | Implementation | Test | Status |
|---|---|---|---|---|
| Accounts (clients) with assigned users via `account_user` pivot | Ch. 3, M2 Accounts | `App\Domain\Accounts\Services\AccountService`, `App\Models\Account`, `App\Models\AccountUser` | `AccountAccessTest` | Done |
| Account-scoped audit trail | Ch. 3, M8 Administration | `App\Domain\Accounts\Events\AccountAudited`, `App\Listeners\LogAccountAuditEntry` | `AuditLoggingTest` | Done |
| Employee sees only accounts they're assigned to | Ch. 1, Objectives — non-invasive, least-privilege | `App\Http\Controllers\Employee\AccountController`, `AccountPolicy` | `AccountAccessTest` | Done |

## M3 — Workload Management

| Requirement | Manuscript location | Implementation | Test | Status |
|---|---|---|---|---|
| Complexity Tier (S/M/L) with configurable weight, snapshotted per task | Ch. 3, Step 4 — Workload Score | `App\Domain\Tasks\Enums\ComplexityTier`, `tasks.complexity_weight` column, `SettingsService::decimal('complexity_weight_*')` | `ModelRelationshipsTest` | Partial (no dedicated weight-snapshot unit test yet) |
| Workload Score = Σ complexity weights of `in_progress` tasks | Ch. 3, Step 4 | `App\Domain\Tasks\Services\WorkloadScoreService::scoreForUser()` / `scoresForActiveUsers()` | `TeamWorkloadChartTest` ("only counts in_progress tasks toward the workload score") | Done |
| Over-allocation warning when assigning past `workload_threshold` (warns, never silently blocks or allows; manager may override) | CLAUDE.md required feature test; capacity-engine skill "Pre-assignment over-allocation check" | `App\Domain\Tasks\Services\TaskAssignmentService::evaluate()`/`assign()`, `App\Domain\Tasks\Exceptions\OverAllocationWarning`, `App\Livewire\Tasks\AssignTaskModal` | `TaskAssignmentTest` (warns without creating the task; creates + logs override on confirm; resets a stale warning when the candidate changes) | Done |
| Override is audited | CLAUDE.md Rule 6/7; schema skill audit action list (`over_allocation_override`) | `App\Events\TaskAssigned`, `App\Listeners\WriteAuditLog::handleTaskAssigned` | `TaskAssignmentTest` | Done |
| `OverAllocationDetected` event + notification (post-reassignment case) | Ch. 3, M6 Process Optimization | `App\Events\OverAllocationDetected`, `App\Listeners\NotifyOverAllocation`, fired from `RecommendationReviewService::checkOverAllocation()` | — | Partial (wired, no dedicated test found) |

## M4 — Time Tracking

| Requirement | Manuscript location | Implementation | Test | Status |
|---|---|---|---|---|
| Timer-based logging (start/stop, one active timer per user) | Ch. 3, M4 Time Tracking | `App\Domain\TimeTracking\Services\TimerService` (`start`, `stop`, `active`), `App\Livewire\TimeTracking\TimerWidget` | `LoggingMethodsTest` ("logs a day of work via the timer") | Done |
| Manual time entry | Ch. 3, M4 Time Tracking | `App\Domain\TimeTracking\Services\ManualTimeEntryService`, `App\Livewire\TimeTracking\ManualEntryForm`, `App\Livewire\Forms\ManualTimeLogForm` | `LoggingMethodsTest` ("logs a day of work via manual entry") | Done |
| Production-sheet CSV import with row-level validation and rejection reasons | Ch. 3, M4 Time Tracking | `App\Domain\TimeTracking\Services\ProductionSheetImportService`, `ProductionSheetParser`, `ProductionSheetRowValidator`, `App\Domain\TimeTracking\Imports\ProductionSheetImport`, `App\Livewire\TimeTracking\ProductionSheetImport` | `ProductionSheetImportTest`, `LoggingMethodsTest` | Done |
| `entry_method` recorded per log (`timer`/`manual`/`import`) | Ch. 3, M4 Time Tracking | `App\Domain\TimeTracking\Enums\EntryMethod`, `time_logs.entry_method` | `LoggingMethodsTest` | Done |
| Leave hours (`H_leave`) loggable and distinct from production hours | Ch. 3, Step 1 | `App\Domain\TimeTracking\Enums\HourType` (`Leave`, `Production`, `NonProduction`) | `CapacityCalculationServiceTest` | Done |
| Employees cannot view another employee's raw time logs (3-layer enforcement) | CLAUDE.md Rule 7 | Route middleware (`auth`), `TimeLogPolicy`, query scoping in `MyDashboard` / `TimeLogReportService` | `TimeLogAuthorizationTest`, `MyDashboardTest`, `TimeLogReportScopingTest` | Done |
| Production-sheet import restricted to managers/administrators | Ch. 3, Security NFR | `role:` middleware + `TimeLogPolicy` on the import route | `TimeLogAuthorizationTest` | Done |
| 48-hour time-log edit window | `settings.timelog_edit_window_hours` (CLAUDE.md calibration table) | `SettingsService::int('timelog_edit_window_hours')` | — | Partial (setting exists; no test found enforcing the window) |

## M5 — Capacity Forecasting

| Requirement | Manuscript location | Implementation | Test | Status |
|---|---|---|---|---|
| `H_poss = H_base − H_leave` | Ch. 3, Step 2 | `CapacityCalculationService::recalculateForMonth()` | `CapacityCalculationServiceTest`, `PerformanceTierResolverTest` | Done |
| `H_thresh = H_poss × U_target` | Ch. 3, Step 3 | `CapacityCalculationService::recalculateForMonth()` | `CapacityCalculationServiceTest`, `PerformanceTierResolverTest` | Done |
| Monthly Performance % = `(H_prod / H_thresh) × 100` | Ch. 3, Step 5 | `PerformanceTierResolver::resolve()` | `CapacityCalculationServiceTest`, `PerformanceTierResolverTest` | Done |
| Performance Tier (`below`/`acceptable`/`over`) via configurable thresholds | Ch. 3, Step 5 | `PerformanceTierResolver::tierForPercentage()`, `App\Domain\Capacity\Enums\PerformanceTier`, `settings.perf_below_max` / `perf_over_min` | `PerformanceTierResolverTest`, `CapacityCalculationServiceTest` | Done |
| Divide-by-zero guard for full-month leave (`H_thresh = 0`) | Ch. 3, Step 5 edge case | `PerformanceTierResolver::resolve()` returns `PerformanceResult(percentage: null, tier: NotApplicable)` | `CapacityCalculationServiceTest` ("never divides by zero for an employee on full-month leave") | Done |
| No monthly baseline configured → explicit failure, not a guess | Ch. 3, Step 1 edge case; Q4 panel answer (cold-start) | `CapacityCalculationService::recalculateForMonth()` throws `RuntimeException` | `CapacityCalculationServiceTest` ("throws instead of guessing when no monthly baseline is configured") | Done |
| `capacity:recalculate` artisan command | CLAUDE.md Commands table | `App\Console\Commands\RecalculateCapacity` | — | Partial (covered indirectly via `CapacityCalculationServiceTest`; no command-level test) |
| `capacity_metrics` snapshots `h_base` and `u_target` so history never changes retroactively | CLAUDE.md DB Conventions | `capacity_metrics` migration columns `h_base`, `u_target` | `ModelRelationshipsTest` | Partial |
| Monthly At-a-Glance dashboard, restricted to manager/administrator | Ch. 3, M7 Reporting | `App\Livewire\Dashboard\MonthlyAtAGlance` | `MonthlyAtAGlanceTest` | Done |
| Quarterly Performance % = mean of the three monthly percentages | Ch. 3, Step 6 | `App\Livewire\Dashboard\QuarterlyAtAGlance` | `QuarterlyAtAGlanceTest` ("averages the three monthly percentages into the quarterly percentage and tier") | Done |
| Employee "My Dashboard" — own capacity only | CLAUDE.md Rule 7 | `App\Livewire\Dashboard\MyDashboard` | `MyDashboardTest` | Done |
| Team workload chart (workload score per employee) | Ch. 3, M3/M7 | `App\Livewire\Dashboard\TeamWorkloadChart` | `TeamWorkloadChartTest` | Done |

## M6 — Process Optimization

| Requirement | Manuscript location | Implementation | Test | Status |
|---|---|---|---|---|
| Optimization engine never writes to `tasks` directly; only inserts recommendations | CLAUDE.md Rule 1 | `redistribution_recommendations` table, `RedistributionRecommendation` model | — | **Gap** (see below — detection engine not yet implemented) |
| Bottleneck detection (variance vs. historical average, account/tier/standard-hours fallback) | Ch. 3, M6 Process Optimization | `App\Domain\Optimization\Services\BottleneckDetectionService`, `App\Console\Commands\DetectBottlenecks` (`optimization:detect`, scheduled daily at 01:15) | `BottleneckDetectionTest` (account/tier/standard_hours basis, threshold guard) | Done |
| Redistribution recommendation is self-explaining, ranked by spare workload/availability, excludes over-tier/over-threshold candidates | Ch. 3, M6 Process Optimization | `App\Domain\Optimization\Services\RedistributionRecommender` | `BottleneckDetectionTest` (candidate exclusion, no-candidate fallback) | Done |
| Duplicate pending recommendations are prevented per task | Ch. 3, M6 Process Optimization | `RedistributionRecommender::recommend()` pending-row guard | `BottleneckDetectionTest` ("does not create a duplicate recommendation...") | Done |
| Detection engine never writes `tasks.assigned_to` | CLAUDE.md Rule 1; capacity-engine skill "hard boundary" | `BottleneckDetectionService` only sets `is_bottleneck`; `RedistributionRecommender` only inserts into `redistribution_recommendations` | `BottleneckDetectionTest` ("leaves every tasks.assigned_to unchanged when the detection command runs") | Done |
| Manager reviews and accepts/dismisses a recommendation | Ch. 3, M6 Process Optimization | `App\Domain\Optimization\Services\RecommendationReviewService`, `App\Livewire\Optimization\RecommendationQueue` | `RecommendationQueueTest` ("accept produces exactly one reassignment and one audit row", "dismiss without changing the task") | Done |
| Accepting a recommendation writes a `task_reassignments` row and updates `tasks.assigned_to`, with an audit entry | CLAUDE.md Rule 1; required feature test | `RecommendationReviewService`, `App\Events\RecommendationAccepted`, `App\Listeners\LogRecommendationAccepted` | `RecommendationQueueTest` | Done |
| Dismissing leaves the task unchanged | CLAUDE.md Rule 1; required feature test | `RecommendationReviewService`, `App\Events\RecommendationDismissed`, `App\Listeners\LogRecommendationDismissed` | `RecommendationQueueTest` | Done |
| New recommendation triggers a notification | Ch. 3, M6 | `App\Events\RecommendationCreated`, `App\Listeners\NotifyNewRecommendation`, `App\Notifications\*` | — | Partial (wired; no dedicated test located) |
| Recommendation queue restricted to manager/administrator | Ch. 3, Security NFR | `RecommendationPolicy`, `role:` middleware on `/optimization/recommendations` | `RecommendationQueueTest` | Done |

## M7 — Reporting

| Requirement | Manuscript location | Implementation | Test | Status |
|---|---|---|---|---|
| Monthly capacity PDF matches on-screen dashboard exactly | UAT #14; CLAUDE.md required test intent | `App\Domain\Reporting\Services\MonthlyCapacityReportService`, `App\Domain\Reporting\Services\ReportPdfService`, `resources/views/reports/pdf/*` | `ReportPdfFigureMatchTest` | Done |
| Quarterly capacity report | Ch. 3, M7 | `App\Domain\Reporting\Services\QuarterlyCapacityReportService` | — | Partial (covered indirectly via `QuarterlyAtAGlanceTest`; no PDF-specific test) |
| Team workload distribution report | Ch. 3, M7 | `App\Domain\Reporting\Services\TeamWorkloadReportService`, `App\Domain\Reporting\Exports\TeamWorkloadExport` | `GenerateReportPdfJobTest` | Done |
| Bottleneck report (lists recommendation history) | Ch. 3, M7 | `App\Domain\Reporting\Services\BottleneckReportService`, `App\Domain\Reporting\Exports\BottleneckReportExport` | — | Partial (no dedicated test located) |
| Time log export, scoped per role | CLAUDE.md Rule 7 | `App\Domain\Reporting\Services\TimeLogReportService`, `App\Domain\Reporting\Exports\TimeLogExport` | `TimeLogReportScopingTest` ("never includes another employee's time logs... even when a user_id filter is passed") | Done |
| Audit log report | Ch. 3, M8 Administration | `App\Domain\Reporting\Services\AuditLogReportService`, `App\Domain\Reporting\Exports\AuditLogExport`, `AuditLogPolicy` | — | Partial (policy exists; no report-specific test located) |
| PDF generation runs on the `database` queue, notifies requester on completion | CLAUDE.md Stack table (Queue) | `App\Jobs\GenerateReportPdfJob`, `App\Events\ReportExported`, `App\Listeners\LogReportExported` | `GenerateReportPdfJobTest` | Done |
| Report access restricted by role (e.g., employee cannot queue monthly capacity PDF) | CLAUDE.md Rule 7; Security NFR | `App\Livewire\Reporting\ReportCenter`, `RedistributionRecommendation`/`CapacityMetric` policies | `ReportAuthorizationTest` | Done |

## M8 — Administration

| Requirement | Manuscript location | Implementation | Test | Status |
|---|---|---|---|---|
| Every magic number lives in `settings`, read via `SettingsService` | CLAUDE.md Rule 6 | `App\Domain\Administration\Services\SettingsService`, `App\Models\Setting`, `App\Events\SettingUpdated` | — | Partial (used throughout `CapacityCalculationService`/`PerformanceTierResolver`; no dedicated `SettingsService` unit test located) |
| Monthly baseline (`H_base`) administration | Ch. 3, M8 Administration | `App\Models\MonthlyBaseline`, `App\Events\BaselineUpdated` | `CapacityCalculationServiceTest` (missing-baseline path) | Partial |
| Full audit log of settings/baseline/role/user changes | CLAUDE.md Rule 6/7; Ch. 3 M8 | `App\Listeners\WriteAuditLog`, `App\Models\AuditLog`, `AuditLogPolicy` | `AuditLoggingTest` | Done |
| Admin sets `U_target` per designation; old `capacity_metrics` unchanged | UAT #3 | `Designation.utilization_target`, `capacity_metrics.u_target` snapshot column | — | Partial (schema supports it; no dedicated regression test located) |

## Seeded demo dataset (M8 / Alpha UAT)

| Requirement | Manuscript location | Implementation | Status |
|---|---|---|---|
| 3 client accounts, 1 admin, 2 team leads (0.40), 12 analysts (0.80) | capstone-artifacts skill, "Alpha UAT dataset" | `database/seeders/AccountSeeder`, `DesignationSeeder`, `HistoricalDatasetSeeder` | Done |
| 3 complete months + current month of `monthly_baselines` | same | `database/seeders/MonthlyBaselineSeeder` (computed from weekday count × 8h) | Done |
| ~60 tasks/month across S/M/L, ≥3 completed per tier per account | same | `HistoricalDatasetSeeder::seedTasksForMonth()` | Done — verified 242 tasks seeded, 14–31 completed per tier per account |
| Deliberate tier spread (≥2 below, ≥6 acceptable, ≥2 over) | same | `HistoricalDatasetSeeder` per-employee tier profiles + `CapacityCalculationService` | Done — verified via `capacity_metrics` after seeding: every month shows 2 below / 9–10 acceptable / 2 over |
| ≥1 partial-leave employee, ≥1 full-month-leave employee | same | Bianca Garcia (16h leave, July), Faith Navarro (full `H_base` leave, August → `not_applicable` tier) | Done |
| ≥2 in-progress tasks exceeding 25% variance (bottleneck readiness) | same | `HistoricalDatasetSeeder::seedBottleneckCandidates()` | Done — verified two current-month tasks at ~60% variance over their tier/account historical average |
| Filipino names, `password` for all demo logins | same | `HistoricalDatasetSeeder::createStaff()` | Done — documented in `docs/user-guide.md` |

## Known gaps (report to advisor before defense)

No open gaps remain from the original defense-readiness sweep. All three
previously-tracked items — the seeded demo dataset, the bottleneck detection
engine, and the over-allocation-at-assignment warning — are implemented and
covered by Pest tests. `HistoricalDatasetSeeder` also runs the real
`BottleneckDetectionService` + `RedistributionRecommender` sweep at the end of
seeding, so at least one pending recommendation exists immediately after
`migrate:fresh --seed` — verified: 2 pending recommendations on a clean seed.

The task-creation UI (`AssignTaskModal`) is intentionally minimal — a single
form embedded on the manager account page — since building a full task
management CRUD was not in scope for this gap. Extending it (editing tasks,
reassigning outside the recommendation flow, bulk creation) is future work,
not a defense blocker.

## Defense-readiness checklist — last verified 2026-09-24

| Check | Result |
|---|---|
| `migrate:fresh --seed` succeeds from clean and produces a populated dashboard | ✅ 15 users, 3 accounts, 242 tasks, 568 time logs, 56 capacity metrics |
| Every Pest test passes | ✅ 100 passed (339 assertions) |
| At least one pending recommendation exists on first load | ✅ 2 pending recommendations, produced by the seeder's own bottleneck sweep |
| Monthly drill-down displays the full arithmetic for hand-verification | ✅ confirmed by rendering `MonthlyAtAGlance`'s drill-down against real seeded data — shows H_base, H_leave, H_poss, U_target, H_thresh, H_prod, the percentage formula, and effective availability |
| The three demo logins are documented and work | ✅ `andrea.reyes@` (administrator), `maria.santos@` (manager), `katrina.villanueva@` (employee), all `@mediainsights.demo` / `password` — verified via the real login component, each redirects to its correct role dashboard |
| `APP_DEBUG=false` behaves correctly (no stack traces leak) | ✅ verified empirically: a forced 500 with `app.debug` set to `false` renders Laravel's generic "Server Error" page with no exception message, file path, or stack trace in the response body |

All six items pass. No open defense-readiness blockers.
