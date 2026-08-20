---
name: capstone-artifacts
description: Generates the academic deliverables the capstone manuscript requires — UAT test scripts, ISO/IEC 25010 evaluation instruments, sample input/output reports, the user guide, seeded demo datasets, and traceability tables mapping code to manuscript requirements. Use when the user asks for appendix material, UAT documentation, evaluation forms, defense preparation, screenshots, or anything tied to Chapter 3 testing and implementation.
---

# Capstone Artifacts

This system is graded, not shipped. These deliverables are as important as the
code and are listed in the manuscript's Appendices section.

## Traceability discipline

Every feature must trace to a manuscript requirement. When adding anything,
state which chapter and section justifies it. If nothing does, do not build it.

Maintain `docs/traceability.md`:

| Requirement | Manuscript location | Implementation | Test |
|---|---|---|---|
| `H_poss = H_base − H_leave` | Ch. 3, Step 2 | `CapacityCalculationService::totalPossibleHours()` | `CapacityCalculationTest` |

## Alpha UAT dataset

Alpha testing runs on a **pre-loaded historical dataset** (Ch. 1, Objectives).
`HistoricalDatasetSeeder` must produce, deterministically (fixed seed):

- 3 client accounts with realistic PR/media-monitoring names
- 1 administrator, 2 team leads (`U_target` 0.40), 12 analysts (0.80)
- 3 complete months of `monthly_baselines` plus the current month
- ~60 tasks per month across all three complexity tiers
- Daily time logs producing a deliberate spread of outcomes:
  - at least 2 employees landing `below`
  - at least 6 landing `acceptable`
  - at least 2 landing `over`
  - at least 1 employee with leave hours (tests the `H_poss` reduction)
  - at least 1 employee on full-month leave (tests the divide-by-zero guard)
  - at least 3 completed tasks per tier per account (gives bottleneck detection a real historical average)
  - at least 2 in-progress tasks exceeding the 25% variance (produces live recommendations on first load)
- Filipino names throughout
- Passwords all `password` in the demo seeder, documented in the user guide

The demo must be interesting the moment `migrate:fresh --seed` finishes. An
empty dashboard at defense time is a failure.

## UAT test script (`docs/uat-test-script.md`)

Numbered, executable steps with expected results and a pass/fail column.
Group by module, mirror the manuscript's module names, cover both Alpha
(calculation accuracy) and Beta (usability) purposes.

Must include:

| # | Scenario | Expected |
|---|---|---|
| 1 | Login as each of the three roles | lands on the correct dashboard, sees only permitted nav |
| 2 | Employee attempts to open a manager URL directly | 403 |
| 3 | Admin sets `U_target` for a designation | value persists; new metrics use it; **old metrics unchanged** |
| 4 | Manager creates a Large task and assigns it to an at-threshold employee | warning appears with real numbers and alternatives |
| 5 | Employee starts and stops a timer | log created with correct duration and `entry_method = 'timer'` |
| 6 | Employee manually logs 8h leave | `H_leave` rises, `H_poss` falls, `H_thresh` falls |
| 7 | Import a production sheet | preview shows valid + rejected rows with reasons |
| 8 | Run `capacity:recalculate` | figures match a hand calculation for a chosen employee |
| 9 | Quarterly view | equals the mean of the three monthly percentages |
| 10 | Overrun task triggers detection | recommendation appears; `tasks.assigned_to` unchanged |
| 11 | Manager dismisses a recommendation | status `dismissed`, no reassignment |
| 12 | Manager accepts a recommendation | reassignment row written, task updated, audit entry present |
| 13 | Employee views own capacity | own data only; no teammate data reachable |
| 14 | Export monthly PDF | figures match the on-screen dashboard |

## ISO/IEC 25010 evaluation instrument (`docs/evaluation-instrument.md`)

Five-point Likert (5 Strongly Agree → 1 Strongly Disagree), scored by weighted
mean, per the protocol in reference [21]. Three characteristics in scope:

- **Functional Suitability** — completeness, correctness, appropriateness (≈5 items)
- **Performance Efficiency** — time behaviour, resource utilization, capacity (≈4 items)
- **Usability** — appropriateness recognizability, learnability, operability,
  user error protection, UI aesthetics, accessibility (≈6 items)

Produce two versions: IT Experts (n=5) and End Users (n=30). Include the
weighted-mean interpretation scale (e.g. 4.21–5.00 Excellent, 3.41–4.20 Very Good,
and so on) and a computation worksheet.

Note for the manuscript: the standard is cited as both `:2011` and `:2023` in
different sections — pick one and use it consistently everywhere.

## Sample input/output reports (Appendix)

Generate and export as PDF from the seeded dataset:
- At-a-Glance Monthly dashboard
- At-a-Glance Quarterly dashboard
- Team workload distribution
- Bottleneck report
- A sample production-sheet CSV (the input side)
- A redistribution recommendation card

Store under `docs/samples/`.

## User guide (`docs/user-guide.md`)

Required Appendix item. One section per role, screenshot placeholders marked
`![](samples/xx.png)`, written for a non-technical analyst. Cover: logging in,
logging time three ways, reading your own capacity, creating and assigning tasks,
interpreting the tier colours, reviewing recommendations, and admin configuration.

## Defense-readiness checks

Before any demo, verify:
- [ ] `php artisan migrate:fresh --seed` succeeds from clean and produces a populated dashboard
- [ ] every Pest test passes
- [ ] at least one pending recommendation exists on first load
- [ ] the monthly drill-down displays the full arithmetic for hand-verification
- [ ] the three demo logins are documented and work
- [ ] `APP_DEBUG=false` behaves correctly (no stack traces leak)

## Questions the panel will ask — have an answer in the code

1. "Where is the AI?" → heuristic, rule-based decision support; symbolic AI;
   justified by references [14], [26]–[29]; ML explicitly ruled out in Ch. 1 Limitations.
2. "Why 25% variance / weight 5 / threshold 12?" → configurable settings,
   calibrated with the beneficiary and refined during Beta UAT.
3. "What if there's no history?" → documented cold-start limitation; the system
   falls back to `standard_hours` and reports which basis it used.
4. "How is this not surveillance?" → show the schema: no screenshot, keystroke,
   app, or location table exists; employees cannot query each other.
5. "Why Laravel when Chapter 2 says FastAPI/Django?" → the objective specifies
   MVC + RDBMS; reference [24] benchmarks only Python frameworks and at loads
   far above 30 users; reference [25] says choose on read/write profile.
   Update the Ch. 2/Ch. 3 wording before submission.
