---
name: AI-Driven Work OS
description: A rail-concourse split-flap board for capacity status — ruled rows and lamp states, not a hero-metric dashboard.
colors:
  board-ink: "#12151a"
  board-panel: "#1a1e25"
  board-flap: "#20252e"
  board-line: "#333a46"
  board-text: "#f4f2ec"
  board-muted: "#9aa1af"
  board-amber: "#e8a53d"
  board-amber-ink: "#3a2a0d"
  board-red: "#e8756a"
  board-red-ink: "#3a1512"
  board-lit: "#f4f2ec"
  slip-paper: "#f4ecdd"
  slip-ink: "#372f24"
  slip-rule: "#d9cbaf"
typography:
  board:
    fontFamily: "'Big Shoulders', ui-sans-serif, system-ui"
    fontWeight: 800
    letterSpacing: "-0.01em"
  slip:
    fontFamily: "'JetBrains Mono', ui-monospace, monospace"
    fontWeight: 500
    letterSpacing: "normal"
rounded:
  sm: "2px"
  lg: "8px"
spacing:
  segment-x: "16px"
  segment-y: "12px"
components:
  stat-segment:
    backgroundColor: "{colors.board-panel}"
    textColor: "{colors.board-text}"
    padding: "12px 16px"
  tier-badge-acceptable:
    backgroundColor: "{colors.board-lit}"
    textColor: "{colors.board-panel}"
    rounded: "{rounded.sm}"
  tier-badge-below:
    backgroundColor: "{colors.board-amber}"
    textColor: "{colors.board-amber-ink}"
    rounded: "{rounded.sm}"
  tier-badge-over:
    backgroundColor: "{colors.board-red}"
    textColor: "{colors.board-red-ink}"
    rounded: "{rounded.sm}"
  button-recalculate:
    backgroundColor: "{colors.board-amber}"
    textColor: "{colors.board-amber-ink}"
    rounded: "{rounded.sm}"
    padding: "8px 16px"
---

# Design System: AI-Driven Work OS

## Overview

**Creative North Star: "The Rail-Concourse Split-Flap Board"**

This is the first visual surface built for the project, established by the Monthly At-a-Glance capacity screen. It refuses the hero-metric card grid every other capacity tool ships: no floating rounded cards, no drop-shadowed KPI tiles competing for attention. Instead the screen reads as a single physical departures board bolted into a graphite/steel wall — one shared bordered rail holding four readout counters, then a ruled row grid beneath it. The composition *is* the ruling: hairline dividers, not gaps and shadows, separate one figure from the next.

Lamp state carries meaning without depending on color alone: lit/neutral means on target, amber means below the production threshold, red means over it — and every lamp state is always paired with a label and an icon (checkmark, downward mark, warning triangle), never color by itself. A manager scans the board, spots a lamp that needs attention, and clicks the row open to a printed paper slip that shows the exact formula chain (`H_base → H_poss → H_thresh → Performance %`) — the one deliberate material shift in the whole system, confined to that drilldown panel.

**Key Characteristics:**
- One shared bordered rail for summary counters, never separate cards
- Condensed, heavyweight display numerals (Big Shoulders) in a dark graphite ground
- Monospace figures (JetBrains Mono) wherever a value is a computed number, never the display face
- Lamp-state color is never the only signal — label + icon always accompany it
- A warm paper-toned drilldown is the single sanctioned departure from the graphite ground

## Colors

Restrained strategy: a graphite/steel neutral ground and off-white condensed type carry the entire page. Amber and red are reserved exclusively for tier state — never used decoratively, never for emphasis outside a lamp.

### Primary
- **Amber Lamp** (`#e8a53d`, ink `#3a2a0d`): the "below threshold" tier signal — stat-card lamp dot, tier badge fill, workload-bar segment fill, and the Recalculate button (the one interactive accent on the page, sharing the amber vocabulary deliberately since it's an action that changes lamp state).
- **Red Lamp** (`#e8756a`, ink `#3a1512`): the "over threshold" / burnout-risk tier signal — same role set as amber (lamp dot, badge, bar segment), never used elsewhere.
- **Lit White** (`#f4f2ec`): the "on target / acceptable" tier signal, standing in for a physical lit split-flap character. Doubles as the primary text color (`board-text`) — on-target state and default readable text share the same value on purpose, since "on target" is the unmarked, default condition of the board.

### Neutral
- **Board Ink** (`#12151a`): page/panel outermost background — the darkest graphite, the row body (`tbody`) background.
- **Board Panel** (`#1a1e25`): the stat-rail background, table header background, empty-state background — one step lighter than ink, used for "framed" surfaces.
- **Board Flap** (`#20252e`): row hover state and the expanded/selected row background — the mid-tone between panel and ink, signaling "this flap is engaged."
- **Board Line** (`#333a46`): all borders, dividers, and ruled row separators — the single hairline color across the entire surface.
- **Board Muted** (`#9aa1af`): secondary text — labels, sublabels, designation column, uppercase eyebrow-style microcopy.

### Slip (drilldown-only palette)
- **Slip Paper** (`#f4ecdd`): background of the expanded formula drilldown row only — a warm off-white standing in for a printed paper slip. This is a cited, deliberate adaptation, not the primary ground; it never appears outside the drilldown `<td>`.
- **Slip Ink** (`#372f24`): text color on the paper slip.
- **Slip Rule** (`#d9cbaf`): the dashed rule under the "Formula drill-down" heading inside the slip.

### Named Rules
**The One Ground Rule.** The graphite/steel neutral palette (`board-ink`/`board-panel`/`board-flap`/`board-line`) carries every surface except the formula drilldown. Amber and red never appear as decoration — only as a tier-state lamp, always paired with a label and an icon.

**The Shared Lamp Rule.** Tier color (amber/red/lit) is derived from one source — the `PerformanceTier` enum value — and read identically by `x-tier-badge` and `x-workload-bar`. `workload-bar.blade.php` colors its fill segments from the same `tier` prop `tier-badge.blade.php` uses, not from its own independent fill-ratio thresholds, so the badge and the gauge can never disagree about what "on target" means for the same row.

## Typography

**Display Font:** "Big Shoulders" (with `ui-sans-serif, system-ui` fallback) — self-hosted via `@fontsource/big-shoulders` weights 600/700/800/900, exposed as the `font-board` Tailwind family.
**Label/Mono Font:** "JetBrains Mono" (with `ui-monospace, monospace` fallback) — self-hosted via `@fontsource/jetbrains-mono` weights 400/500/600, exposed as the `font-slip` Tailwind family.

**Character:** Big Shoulders is a condensed, heavy-weight display face standing in for split-flap character cells — it carries every heading, label, and stat number. JetBrains Mono is reserved strictly for computed values (hours, percentages, formula figures) so a reader can visually distinguish "a number the system calculated" from "a label describing it," the same distinction a printed slip makes between its form fields and its filled-in figures.

### Hierarchy
- **Display** (extrabold 800, `text-3xl`, leading-none, `font-board`): the four stat-rail counters (Team Size, On Target, Over Threshold, Below Threshold).
- **Headline** (extrabold 800, `text-2xl`, `font-board`): the page header ("At-a-Glance — Monthly").
- **Label** (semibold 600, `text-[0.65rem]`, uppercase, `tracking-[0.1em]`, `font-board`): column headers, stat-rail labels, form field labels — the board's uppercase microcopy register.
- **Body** (regular, `text-sm`, `font-board`): employee names, designation, tier-badge label text.
- **Figure** (medium 500, `text-sm`, `tabular-nums`, `font-slip`): H_prod/H_thresh pairs, workload-bar readouts, every value in the formula drilldown slip.

### Named Rules
**The Two-Face Rule.** `font-board` (Big Shoulders) is for labels, headings, and names — anything read as a word. `font-slip` (JetBrains Mono) is for anything read as a computed figure. A value never appears in the display face; a label never appears in the mono face.

## Layout

The page is a single bordered rail-and-grid composition, not a card layout. The stat-rail is one `rounded-sm border border-board-line bg-board-panel` container holding four `x-stat-card` segments, laid out as a column on mobile (`divide-y`) and a row on `sm:` and up (`sm:divide-x sm:divide-y-0`) — one shared border with internal hairline dividers, never four separate bordered boxes. The row grid below it is a single bordered table (`rounded-sm border border-board-line`) with `divide-y divide-board-line` between rows; there is no card padding or gutter between rows, only the ruled line.

Two columns (Designation, H_prod/H_thresh) collapse at `md:`/`sm:` breakpoints respectively, keeping Employee, Status, and Remaining Capacity as the load-bearing columns on narrow viewports. The formula drilldown expands as a full-width `colspan="5"` row directly beneath the row it belongs to, rather than a modal or side panel — the slip stays physically attached to its row.

## Elevation & Depth

Flat by default. The page uses one soft ambient shadow on the outermost container (`shadow-[0_8px_24px_-8px_rgba(0,0,0,0.5)]`) to lift the whole board off the page background; nothing inside it — no stat segment, no row, no badge — carries its own shadow. Depth inside the board is conveyed by tone stepping (ink → panel → flap), not by shadow layering, consistent with a physical board's own material logic.

## Shapes

Corners are close to square throughout: `rounded-sm` (2px) on the outer board container's inner elements, badges, and buttons; `rounded-lg` (the standard Tailwind default) only on the single outermost wrapper. Lamp dots and tier-badge icon chips are the only circular/soft-square shapes on the page — a deliberate small-scale counterpoint to the otherwise rectilinear, ruled composition. Borders are a single hairline weight (`border-board-line`) everywhere; there is no secondary heavier border weight.

## Components

### Stat Rail Segment (`x-stat-card`)
A stat segment is plain padded content — it renders no border, background, or radius of its own (`px-4 py-3` only). The four segments are visually one rail because the **parent** view wraps them in a single shared `rounded-sm border border-board-line bg-board-panel` container with `divide-x`/`divide-y` between them. This was a deliberate fix: four independently bordered cards is the exact hero-metric grid pattern this system's THESIS refuses.
- **Lamp dot:** a 1.5×1.5 rounded-full dot colored by `tone` (`board-amber` / `board-red` / `board-lit` / `board-line` default), sitting beside the uppercase label.
- **Value:** `text-3xl font-extrabold font-board tabular-nums`, colored to match tone (amber/red text on the below/over tones, default text otherwise).
- **Sublabel:** optional muted caption beneath the value.

### Tier Badge (`x-tier-badge`)
A pill-free inline cluster: a small `rounded-sm` icon chip (lamp) + uppercase label text + optional tabular-nums percentage, all in the tier's color. The icon chip always carries a distinct glyph per tier (check for on-target, downward arrow for below, warning triangle for over, dash for not-applicable) so tier is legible without color — this is a hard system rule, not a style preference (see the direction contract's "never color alone" line).

### Workload Bar (`x-workload-bar`)
A segmented capacity gauge (default 12 segments) rendered as thin `h-2.5` bars in a row, filled left-to-right by ratio of score/threshold. Fill color is read from the same `tier` prop the badge uses (see The Shared Lamp Rule), never computed independently from the ratio. Unfilled segments are `bg-board-line`. Carries an `aria-label` stating percent-of-capacity for accessibility, and a monospace numeric readout (`score / threshold` h) above the bar on `sm:` and up.

### Empty State (`x-empty-state`)
Dashed-border panel (`border-dashed border-board-line bg-board-panel`) centered content, using a three-bar "unlit flap row" SVG icon as the board's own visual vocabulary for "nothing logged" rather than a generic inbox/illustration icon.

### Row / Drilldown (signature component)
Each metric row is a `<tr>` carrying `.animate-flap-cascade` unconditionally as a static class (not a conditional Alpine transition). Its `wire:key` embeds the tier value and `h_prod` (`metric-row-{id}-{tier}-{h_prod}`), so when Livewire's diff detects a changed value it morphs in a **fresh** keyed `<tr>` rather than patching the existing one; the browser plays the CSS keyframe animation automatically on that fresh node's insertion, cascading only the rows whose underlying values actually changed. This replaced an earlier `x-transition` attempt that did not fire reliably against Livewire-morphed nodes in this stack (Livewire 3.8.5) — the CSS-keyframe-on-fresh-node approach is the shipped mechanism, verified via captured mid-flight screenshots, and respects `prefers-reduced-motion: reduce` (animation disabled entirely).

Clicking a row toggles an adjacent full-width drilldown `<tr>` in `bg-slip-paper` — the sole area of the page using the warm paper palette — showing the complete `H_base → H_leave → H_poss → U_target → H_thresh → H_prod` formula chain in a label/value grid, followed by the Performance % and Effective Availability formula sentences spelled out with actual substituted numbers, all in `font-slip` monospace tabular figures.

### Buttons
- **Shape:** `rounded-sm` (2px).
- **Primary (Recalculate):** `bg-board-amber` / `text-board-amber-ink`, uppercase `text-sm font-semibold tracking-[0.04em]`, `px-4 py-2`. Uses the amber lamp color deliberately — recalculation is the one action on the page that can change a lamp's state.
- **Focus:** `focus:ring-2 focus:ring-board-amber focus:ring-offset-2 focus:ring-offset-board-ink` — the offset ring color matches the dark ground so the ring reads as a halo, not a mismatched box.
- **Loading state:** disabled + reduced opacity (`disabled:opacity-60`) with an inline spinning SVG replacing the label text via `wire:loading`.

### Inputs / Fields
Month/Year selects use Tailwind Forms defaults recolored to the board palette: `bg-board-panel`, `border-board-line`, `font-board text-sm`, focus ring/border in `board-amber`. Labels above each field use the Label typographic role (uppercase, `board-muted`, `0.65rem`).

## Do's and Don'ts

### Do:
- **Do** wrap grouped summary counters in one shared bordered rail with internal `divide-x`/`divide-y` hairlines — never separate bordered/shadowed cards per metric.
- **Do** pair every tier-state color (amber/red/lit) with a label and an icon; color alone must never be the only signal of state.
- **Do** derive fill/accent color for any capacity-related component (badges, bars, lamps) from the shared `PerformanceTier` value, never from an independently computed ratio or threshold local to that component.
- **Do** use `font-slip` (JetBrains Mono, tabular-nums) for every computed figure, and `font-board` (Big Shoulders) for every label, heading, and name.
- **Do** confine the `slip-paper`/`slip-ink`/`slip-rule` warm palette to the formula-drilldown surface only.

### Don't:
- **Don't** give a summary/stat component its own border, background, or shadow when it's meant to sit inside a shared rail — that reproduces the hero-metric card grid this system's THESIS explicitly refuses.
- **Don't** rely on Alpine `x-transition` for entry animation on Livewire-morphed rows in this stack; it does not fire reliably. Use a plain CSS `@keyframes` animation applied as a static class instead, keyed off a fresh node via `wire:key`.
- **Don't** add drop shadows to interior elements (rows, badges, segments); the only sanctioned shadow is the single ambient lift on the outermost board container.
- **Don't** introduce amber or red anywhere except as a tier-state lamp signal — they are not general-purpose accent or decorative colors.
