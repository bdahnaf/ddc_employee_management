# Database — DDC Workforce

ER diagram (as built): [`er-diagram.png`](er-diagram.png) · [`er-diagram.svg`](er-diagram.svg) · [`er-diagram.pdf`](er-diagram.pdf)
dbdiagram.io code: [`er-diagram.dbml`](er-diagram.dbml) (paste into https://dbdiagram.io)
Source: [`er-diagram.dot`](er-diagram.dot) — re-render with:

```bash
dot -Tpng -Gdpi=110 er-diagram.dot -o er-diagram.png
dot -Tsvg er-diagram.dot -o er-diagram.svg
dot -Tpdf er-diagram.dot -o er-diagram.pdf
```

> **Built:** `divisions`, `departments`, `designations`, `offices`, `projects`, `employees`, `postings`, `salary_structures`, `users`, `activity_logs` (migrations `database/migrations/2026_10_15_*`).
> **Not built yet:** attendance, leave, holidays, payroll. The diagram will be updated when the structure changes.

---

## The chain

```
divisions ──< departments ──< employees ──< postings >── offices   (HQ)
     │                            │                  └── projects  (site)
     └──────────< projects        └── designations
```

- A **division** has many **departments**, and leads many **projects**.
- A **department** has many **employees**. The employee's division is read through the department, so it is never stored twice.
- An **employee** has many **postings**. Exactly one of them is current: the row whose `end_date` is empty.
- A posting is either **HQ** (`posting_type = hq`, `office_id` filled) or **Project** (`posting_type = project`, `project_id` filled), never both.

## Tables

| Table | What it holds |
|---|---|
| `divisions` | Top-level business units. |
| `departments` | Teams inside a division. Name is unique **within** a division. |
| `designations` | Job titles and grades (e.g. Senior Engineer). |
| `offices` | Head Office (and any other office later). `is_headquarters` marks HQ. |
| `projects` | Project sites: client, location, dates, status, lead division. |
| `employees` | Job details (everyone can see) and personal details (private). Belongs to one department and one designation. `user_id` optionally links a sign-in account. |
| `postings` | Where each employee is or was posted (HQ or project). Also the transfer history. |
| `salary_structures` | Pay breakdown from a date. A change adds a new row and closes the old one, so increments are kept. |
| `users` | Sign-in accounts with a role (`hr`, `head`, `employee`). Sign-in is switched off for now (see [../auth.md](../auth.md)). |
| `activity_logs` | Who changed what, and when. |

## How a transfer works

An admin moves an employee from HQ to a project:

1. Inside one database transaction, the current posting (`end_date` empty) gets `end_date` = the day before the new posting starts.
2. A new posting row is created: `posting_type = project`, `project_id = …`, `start_date = transfer date`, `end_date = null`.
3. An `activity_logs` row is written: `posting.created`.

Nothing is overwritten, so the full history stays in `postings`.

## Rules enforced in code

- One current posting per employee (checked in the transaction above).
- `hq` → `office_id` required, `project_id` must be null; `project` → the opposite.
- A transfer can't start on or before the current posting's start date.
- Divisions, departments, offices, projects and employees use **soft delete** (`deleted_at`) so history and old postings still resolve.
- Can't remove a division with departments, a department with employees, an office or project with staff posted there, or a designation in use.

## Dummy data

`php artisan db:seed` (or `php artisan demo:data`) fills the tables with made-up records: DDC's 7 sectors as divisions, 14 departments, Head Office, 11 projects, 16 designations and 60 employees with posting and salary histories. Every dummy record's code starts with `DEMO-`; `php artisan demo:data --remove` deletes them and leaves real records alone.

## Assumptions (please confirm)

1. Hierarchy is Division → Department → Employee, and the **posting** (HQ or Project) is attached to the employee — HQ and projects do not sit inside a department.
2. A project has one **lead division**, but staff from any department can be posted to it.
3. HQ is stored in an `offices` table, so more offices can be added later. If there is only ever one Head Office, the table simply has one row.
