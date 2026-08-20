---
name: livewire-dashboard
description: UI and dashboard design rules for the AI-Driven Work OS — tier colour system, At-a-Glance monthly and quarterly layouts, workload bars, over-allocation warnings, recommendation queue, timer widget, and Chart.js usage. Use when building or editing any Blade view, Livewire component, Tailwind layout, or chart.
---

# Dashboard & UI

The manuscript commits (via reference [19], Yigitbasioglu & Velcu) to
**cognitive-load-aware** dashboards. That is a testable claim during ISO 25010
usability evaluation. Design accordingly.

## The governing principle

One screen answers one question. The At-a-Glance views show **status at a
glance** — one colour-coded indicator per employee, everything else behind a
click. Do not put twelve charts on one page. Resist the urge to be impressive.

## Tier colour system

Use consistently everywhere — dashboards, tables, exports, PDFs.

| Tier | Meaning | Tailwind |
|---|---|---|
| `below` | under-utilized | `bg-amber-50 text-amber-700 border-amber-200` |
| `acceptable` | on target | `bg-emerald-50 text-emerald-700 border-emerald-200` |
| `over` | over-utilized / burnout risk | `bg-rose-50 text-rose-700 border-rose-200` |
| `not_applicable` | full-month leave | `bg-slate-50 text-slate-500 border-slate-200` |

Never encode meaning by colour alone — always pair with a text label. Some
evaluators will be colour-blind and accessibility is inside the ISO 25010
usability characteristic.

Complexity tiers: Small `slate`, Medium `sky`, Large `violet`.

## Components to build

```
<x-tier-badge :tier="$metric->performance_tier" :percentage="$metric->performance_percentage" />
<x-workload-bar :score="$score" :threshold="$threshold" />
<x-stat-card label="" value="" sublabel="" />
<x-complexity-badge :tier="$task->complexity_tier" />
<x-empty-state message="" />
```

## At-a-Glance Monthly (`Livewire\Dashboard\MonthlyAtAGlance`)

- Month/year selector, defaults to the current period
- Four summary stat cards: team size, on-target count, over-utilized count, under-utilized count
- One row per employee: name, designation, `H_prod / H_thresh`, percentage,
  tier badge, remaining-capacity bar
- Row click → drill-down panel showing `H_base`, `H_leave`, `H_poss`, `U_target`,
  `H_thresh`, `H_prod` — **the full arithmetic, visible**. This is the single
  most valuable screen at your defense: an examiner can verify the formula by eye.
- "Recalculate" button (manager/admin) with a loading state
- `wire:poll.30s`

## At-a-Glance Quarterly (`QuarterlyAtAGlance`)

- Quarter/year selector
- Matrix: employees down, three months across + a quarterly column
- Each cell a tier badge with its percentage
- Footer note: quarterly = arithmetic mean of the three monthly percentages

## Team Workload (`TeamWorkloadChart`)

Horizontal Chart.js bar chart, one bar per employee, with a threshold reference
line. Bars past the threshold render in the `over` colour. Sorted descending.

## Over-allocation warning (`Tasks\AssignTaskModal`)

The most important interaction in the system. When `prospective > threshold`:

```
⚠ Workload warning

Juan dela Cruz is at 11 of 12 workload points.
Adding this Large task (5 points) puts him at 16 — 33% over threshold.

Suggested alternatives:
  Maria Santos    4 / 12   ·  22.0h remaining capacity   [Assign]
  Ana Reyes       6 / 12   ·  14.5h remaining capacity   [Assign]

[ Assign to Juan anyway ]   [ Cancel ]
```

Show real numbers, never a vague "workload is high". The override stays
available — this is decision support, not enforcement — but it is audit-logged.

## Recommendation queue (`Optimization\RecommendationQueue`)

Card per pending recommendation:
- Task title + reference, current assignee
- Evidence line: actual vs. historical average, variance %
- Suggested assignee with their score and remaining capacity
- The plain-language `reason`
- `[Accept & Reassign]` `[Dismiss]`

Accepting opens a confirmation with a required reason field. Never one-click
reassign — the manual-approval constraint should be visible in the UI, not just
the code.

## Employee dashboard (`my/dashboard`)

Own data only: own percentage and tier, own active tasks with complexity badges,
own workload score, hours logged this month. **No comparison to teammates, no
ranking, no leaderboard.** The non-invasive design principle applies to the UI as
much as the database — an employee should never see themselves ranked against a
colleague.

## Timer widget (`TimeTracking\ActiveTimer`)

- Persistent in the employee layout
- Start → select task → running elapsed time via Alpine (client-side tick,
  server holds truth)
- Stop → writes a `time_log` with `entry_method = 'timer'`
- Only one running timer per user; starting a second stops the first with a notice
- Survives a page refresh (read the open timer from the server on mount)

## Charts

Chart.js only, loaded via Vite. Keep to bar and line. No pie charts, no
gauges, no animations longer than 300ms. Always provide a data table fallback
beneath or behind a toggle — charts alone fail accessibility review.

## Layout

- Tailwind, mobile-considerate but desktop-first (managers use laptops)
- Sidebar navigation that differs by role; never render a link the user cannot access
- Flash messages top-right, auto-dismiss 4s
- Empty states everywhere — a fresh install must not look broken
- Loading states on every `wire:click` that touches the database

## Do not build

Dark mode toggle · onboarding tour · animated splash · notification sounds ·
drag-and-drop kanban · avatar uploads · theme customization. None is in scope
and all cost sprint time that belongs to the three core modules.
