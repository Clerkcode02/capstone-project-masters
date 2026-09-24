# User Guide — AI-Driven Work Operating System

This guide explains how to use the system day to day. It assumes no prior
technical background. Screenshots are marked as placeholders (`![](samples/xx.png)`)
and should be replaced with real captures from the seeded demo dataset before
the guide is printed or submitted.

Demo login credentials (seeded by `HistoricalDatasetSeeder`, demo dataset only —
never use these in production):

| Role | Name | Email | Password |
|---|---|---|---|
| Administrator | Andrea Reyes | `andrea.reyes@mediainsights.demo` | `password` |
| Manager / Team Lead | Maria Santos | `maria.santos@mediainsights.demo` | `password` |
| Manager / Team Lead | Ramon Cruz | `ramon.cruz@mediainsights.demo` | `password` |
| Employee / Sr. Reports Analyst | Katrina Villanueva | `katrina.villanueva@mediainsights.demo` | `password` |
| Employee / Jr. Reports Analyst | Kevin Domingo | `kevin.domingo@mediainsights.demo` | `password` |

The full roster is 1 administrator, 2 team leads, and 12 analysts across three
client accounts (Solstice Public Relations, Harborlight Media Group, Meridian
Brand Communications), covering three complete months of history plus the
current month.

> The system does not track screenshots, keystrokes, browsing activity, idle
> time, webcam, or location. It only records the hours you choose to log.
> This is a deliberate design decision, not a missing feature.

---

## 1. Logging In (All Roles)

1. Open the system URL in your browser.
2. Enter your email address and password.
3. Click **Log In**.
4. You will land on the dashboard for your role automatically — you do not
   need to choose one.

![](samples/01-login.png)

If you forget your password, use **Forgot your password?** on the login page.
An email with a reset link will be sent to you.

---

## 2. For Employees / Analysts

### 2.1 Logging your time — three ways

You can log your work hours in any of three ways. Use whichever fits how you
work that day.

**Method A — Timer (recommended for real-time tracking)**

1. From your dashboard, open **Time Tracking**.
2. Select the client account and task you're working on.
3. Choose the hour type (Production or Non-Production).
4. Click **Start**. The timer runs in the background while you work.
5. When you stop working on that task, click **Stop**. Your hours are saved
   automatically — you don't need to enter the duration yourself.

![](samples/02-timer-widget.png)

**Method B — Manual Entry (for hours you forgot to time, or leave)**

1. Open **Time Tracking → Manual Entry**.
2. Choose the date, account, task (if applicable), hour type, and duration.
3. If you're logging approved leave, choose hour type **Leave**.
4. Click **Save**.

![](samples/03-manual-entry.png)

**Method C — Production Sheet Import (for managers/administrators only)**

If your team uses a shared production sheet, your manager can import it in
bulk on your behalf — see section 3.2.

### 2.2 Reading your own capacity ("My Dashboard")

Your dashboard shows only your own numbers — never a teammate's.

| Term | What it means |
|---|---|
| H_base | The standard number of hours expected this month for everyone. |
| H_leave | Your approved leave hours this month. |
| H_poss | Your possible working hours this month (H_base minus your leave). |
| H_thresh | Your production target for the month, based on your designation. |
| H_prod | The hours you've actually logged as production so far. |
| Performance % | How close you are to your target: (H_prod ÷ H_thresh) × 100. |

![](samples/04-my-dashboard.png)

**Tier colours:**

- 🔴 **Below** — you're under your target. This is a prompt to check in with
  your team lead, not a penalty.
- 🟢 **Acceptable** — you're on target.
- 🟡 **Over** — you're significantly above target, which can also indicate
  overwork worth flagging.

### 2.3 Reviewing tasks assigned to you

Open **My Accounts** to see the client accounts you're assigned to, and the
tasks under each. Task cards show the complexity tier (Small / Medium /
Large) with a colour swatch.

![](samples/05-my-tasks.png)

---

## 3. For Managers / Team Leads

### 3.1 Creating and assigning tasks

1. Open the client account you manage.
2. Click **New Task**.
3. Enter the task title and choose a Complexity Tier (Small / Medium / Large).
4. Assign it to an analyst on your team.
5. If the analyst is already near their workload limit, the system shows a
   warning with their current workload score, the new task's weight, and
   suggested alternative assignees. You can still proceed if you judge it
   necessary — the system warns, it does not block.

![](samples/06-new-task-warning.png)

### 3.2 Importing a production sheet

1. Open **Time Tracking → Import Production Sheet**.
2. Download the CSV template if you don't already have one filled in.
3. Upload your completed sheet.
4. Review the preview: valid rows are shown separately from rejected rows,
   each rejected row with a plain-language reason (e.g. duplicate entry,
   invalid date).
5. Confirm the import. Only valid rows are saved.

![](samples/07-import-preview.png)

### 3.3 Reading team dashboards

- **Monthly At-a-Glance** — every team member's H_poss/H_thresh/H_prod/%/tier
  for the current month. Click a row to see the full arithmetic behind it.
- **Quarterly At-a-Glance** — the same, averaged across the last three months.
- **Team Workload Chart** — a bar per employee showing their current
  workload score (sum of complexity weights on their in-progress tasks).

![](samples/08-monthly-at-a-glance.png)
![](samples/09-quarterly-at-a-glance.png)
![](samples/10-team-workload-chart.png)

### 3.4 Reviewing redistribution recommendations

When the system notices a task is taking significantly longer than similar
tasks historically have, it raises a recommendation — it never reassigns the
task by itself.

1. Open **Optimization → Recommendations**.
2. Read the reasoning shown (which task, how far over the historical
   average, and who is suggested to take it).
3. Click **Accept** to reassign the task to the suggested employee, or
   **Dismiss** to leave it as is. Either action is recorded in the audit log.

![](samples/11-recommendation-queue.png)

### 3.5 Exporting reports

Open **Reports**, choose a report type (Monthly Capacity, Quarterly Capacity,
Team Workload, Bottleneck), and click **Generate**. Larger reports are
processed in the background — you'll get a notification when the PDF or
Excel file is ready to download.

![](samples/12-report-center.png)

---

## 4. For Administrators

### 4.1 Managing accounts and users

Open **Admin → Accounts** to create client accounts and assign users to them.
Open **Admin → Users** (via Designations/Roles settings) to create user
accounts, assign a role (administrator/manager/employee) and a designation
(e.g. Team Lead, PR Analyst).

![](samples/13-admin-accounts.png)

### 4.2 Configuring settings

All calibration values used in calculations live under **Admin → Settings** —
none of them are hardcoded in the system, so they can be adjusted as the
beneficiary's needs change:

| Setting | What it controls |
|---|---|
| Complexity weights (Small/Medium/Large) | How much each task tier contributes to workload score |
| Workload threshold | The workload score that triggers an over-allocation warning |
| Performance bands (below/over cutoffs) | Where the tier colours switch |
| Bottleneck variance % | How far above historical average a task must run before a recommendation is raised |
| Time-log edit window (hours) | How long after logging an entry can still be edited |

Every change here is written to the audit log with the old value, new value,
who made the change, and when.

![](samples/14-admin-settings.png)

### 4.3 Setting monthly baselines and utilization targets

1. Open **Admin → Monthly Baselines**.
2. Set `H_base` (standard hours) for the upcoming month before it starts —
   capacity calculations for a month will fail with a clear error if no
   baseline exists yet, rather than guessing a number.
3. Open **Admin → Designations** to set `U_target` per designation (e.g. Team
   Lead 0.40, all analyst designations 0.80). Changing this only affects
   future calculations — past months keep the values that were in effect
   when they were calculated.

![](samples/15-admin-baselines.png)

### 4.4 Reviewing the audit log

Open **Admin → Audit Log** to see every login, settings change, user change,
recommendation decision, and report export, each with who did it and when.

![](samples/16-audit-log.png)

---

## 5. Frequently Asked Questions

**Does the system watch what I do on my computer?**
No. It has no screenshot, keystroke, app-usage, idle-time, webcam, or
location tracking of any kind — only the hours you choose to log through the
timer, manual entry, or your manager's production-sheet import.

**Can my coworkers see my time logs?**
No. Employees can only see their own time logs and capacity. Managers and
administrators can see team-level data appropriate to their role.

**Will the system ever reassign my tasks automatically?**
No. It only ever suggests a reassignment to a manager, who must explicitly
accept it before anything changes.

**What if I disagree with a Performance Tier result?**
Bring it to your team lead — every figure on the dashboard can be drilled
down into its full arithmetic (H_base, H_leave, H_poss, U_target, H_thresh,
H_prod) so it can be checked by hand.
