# ISO/IEC 25010:2011 Software Quality Evaluation Instrument

**System:** AI-Driven Work Operating System
**Standard:** ISO/IEC 25010:2011, Product Quality Model. This standard is
cited as `:2011` consistently throughout the manuscript and all appendices
(the `:2023` revision is not used anywhere in this project, per the
CLAUDE.md calibration note).

**Scale:** 5-point Likert
5 = Strongly Agree · 4 = Agree · 3 = Neutral · 2 = Disagree · 1 = Strongly Disagree

**Scope:** three characteristics, per the manuscript's evaluation protocol
(reference [21]):

1. **Functional Suitability** — functional completeness, correctness, appropriateness
2. **Performance Efficiency** — time behaviour, resource utilization, capacity
3. **Usability** — appropriateness recognizability, learnability, operability,
   user error protection, user interface aesthetics, accessibility

Two respondent groups complete separate but parallel versions of the same
instrument:

- **IT Expert version** (target n = 5) — software developers, QA, or IT
  professionals who can assess technical correctness and efficiency directly.
- **End User version** (target n = 30) — Media Insights Group / Agility PR
  Solutions staff (administrators, managers, employees) who use the system
  in their actual workflow.

---

## Weighted-Mean Interpretation Scale

Each item's weighted mean (WM) is computed as:

```
WM = Σ(fᵢ × wᵢ) / N
```

where `fᵢ` is the number of respondents who chose rating `wᵢ` (1–5), and `N`
is the total number of respondents for that item.

| Weighted Mean Range | Descriptive Rating |
|---|---|
| 4.21 – 5.00 | Excellent |
| 3.41 – 4.20 | Very Good |
| 2.61 – 3.40 | Good |
| 1.81 – 2.60 | Fair |
| 1.00 – 1.80 | Poor |

The overall rating per characteristic is the mean of its item weighted means.
The overall system rating is the mean of the three characteristic means.

---

## Part A — IT Expert Version

Instructions: Rate each statement based on your technical review of the
system (code walkthrough, UAT participation, and/or live demonstration).

### A1. Functional Suitability

| # | Item |
|---|---|
| FS-1 | The system implements all functions specified for capacity forecasting, workload management, time tracking, and process optimization. |
| FS-2 | The calculated results (H_poss, H_thresh, Performance %, Workload Score) are arithmetically correct and verifiable by hand. |
| FS-3 | The functions provided are appropriate for the task of WFH capacity and workload decision support (no unnecessary or missing functions). |
| FS-4 | The system correctly restricts each function to the appropriate user role (administrator, manager, employee). |
| FS-5 | The redistribution recommendation workflow produces decision support without ever silently reassigning a task. |

### A2. Performance Efficiency

| # | Item |
|---|---|
| PE-1 | Dashboard pages (Monthly/Quarterly At-a-Glance, Team Workload) load within an acceptable time for ~30 concurrent users. |
| PE-2 | The `capacity:recalculate` and PDF-export jobs complete without noticeable strain on server resources. |
| PE-3 | The system's resource utilization (CPU/DB load) is reasonable given a plain PHP + MySQL + Apache/Nginx stack with no caching layer beyond the database. |
| PE-4 | The system's capacity (concurrent users, data volume) is sufficient for the beneficiary's ~30-user scale. |

### A3. Usability

| # | Item |
|---|---|
| US-1 | Users can recognize at a glance whether a feature (timer, manual entry, import, recommendations) is appropriate to their current task. |
| US-2 | A new user can learn to log time and read their own dashboard with minimal instruction. |
| US-3 | Core operations (start/stop timer, accept/dismiss a recommendation) can be completed efficiently, in few steps. |
| US-4 | The system prevents or warns against user errors (e.g., over-allocation, duplicate time-log rows) rather than failing silently. |
| US-5 | The interface (tier colours, warnings, dashboards) is visually clear and uncluttered. |
| US-6 | The interface is usable by staff with varying levels of technical skill (accessibility of language and controls). |

---

## Part B — End User Version

Instructions: Rate each statement based on your day-to-day use of the system
during Beta testing.

### B1. Functional Suitability

| # | Item |
|---|---|
| FS-1 | The system lets me do everything I need for my role (logging time, viewing my capacity, managing tasks, or reviewing recommendations, as applicable). |
| FS-2 | The numbers shown on my dashboard (my hours, my performance percentage) match what I actually did. |
| FS-3 | The features available to me are the ones I actually need — nothing important is missing, nothing feels unnecessary. |
| FS-4 | I only see information relevant to my own role and, where applicable, my own team. |
| FS-5 | When a task is reassigned, I understand that a manager approved it — the system never reassigns work on its own. |

### B2. Performance Efficiency

| # | Item |
|---|---|
| PE-1 | Pages load quickly enough that I don't feel like I'm waiting. |
| PE-2 | Generating a report (PDF/Excel) does not slow down the rest of the system for me. |
| PE-3 | The system feels responsive even during busy periods (e.g., month-end). |
| PE-4 | The system handles my team's normal daily use without slowdowns or errors. |

### B3. Usability

| # | Item |
|---|---|
| US-1 | I can tell what each part of the dashboard is for just by looking at it. |
| US-2 | I learned to use the system quickly, without needing a lot of training. |
| US-3 | I can complete my common tasks (logging time, checking my capacity, reviewing a recommendation) in just a few clicks. |
| US-4 | The system warns me clearly before I make a mistake (e.g., assigning too much work, duplicate entries). |
| US-5 | The layout, colours, and text are easy on the eyes and easy to understand. |
| US-6 | I did not need technical knowledge to use this system comfortably. |

---

## Computation Worksheet

Use one worksheet per characteristic, per respondent group (IT Expert / End User).

### Example worksheet — Functional Suitability (fill in per item)

| Item | f(5) | f(4) | f(3) | f(2) | f(1) | N | Σ(f×w) | WM | Descriptive Rating |
|---|---|---|---|---|---|---|---|---|---|
| FS-1 | | | | | | | | | |
| FS-2 | | | | | | | | | |
| FS-3 | | | | | | | | | |
| FS-4 | | | | | | | | | |
| FS-5 | | | | | | | | | |
| **Characteristic Mean** | | | | | | | | **=mean(WM column)** | |

Repeat the same table shape for Performance Efficiency (PE-1..4) and Usability
(US-1..6).

### Overall Summary Worksheet

| Characteristic | IT Expert WM | End User WM | Combined Mean | Descriptive Rating |
|---|---|---|---|---|
| Functional Suitability | | | | |
| Performance Efficiency | | | | |
| Usability | | | | |
| **Overall System Rating** | | | **=mean of the three combined means** | |

### Worked example (illustrative only — replace with real data)

Suppose FS-1 receives responses from 5 IT experts: three rate it 5, two rate
it 4.

```
Σ(f×w) = (3×5) + (2×4) = 15 + 8 = 23
N = 5
WM = 23 / 5 = 4.60  →  Excellent
```

---

## Respondent Information (record once per survey, not per item)

| Field | IT Expert Version | End User Version |
|---|---|---|
| Name (optional) | | |
| Role | Developer / QA / IT Professional | Administrator / Manager / Employee |
| Years of relevant experience | | |
| Date administered | | |

## Notes for administration

- Administer immediately after the corresponding UAT scenarios in
  `docs/uat-test-script.md` so ratings reflect direct hands-on experience,
  not a cold read of the interface.
- Keep IT Expert and End User response sheets separate; do not average raw
  scores across groups before computing each group's own weighted mean —
  the "Combined Mean" column exists precisely to reconcile the two
  perspectives after each is scored independently.
- If a respondent leaves an item blank, exclude that respondent from `N` for
  that item only; do not substitute a default value.
