# Database — DDC Workforce

ER diagram: [`er-diagram.png`](er-diagram.png) · [`er-diagram.svg`](er-diagram.svg) · [`er-diagram.pdf`](er-diagram.pdf)
Source (Graphviz): [`er-diagram.dot`](er-diagram.dot) — re-render with:

```bash
dot -Tpng -Gdpi=110 er-diagram.dot -o er-diagram.png
dot -Tsvg er-diagram.dot -o er-diagram.svg
dot -Tpdf er-diagram.dot -o er-diagram.pdf
```

> **Draft 1.** Built on the assumptions listed at the bottom. Change those first, then the migrations.

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

| Table | What it holds | Who writes it |
|---|---|---|
| `divisions` | Top-level business units. `head_employee_id` is optional. | Admin |
| `departments` | Units inside a division. Name is unique **within** a division. | Admin |
| `designations` | Job titles and grades (e.g. Senior Engineer). | Admin |
| `offices` | Head Office (and any other office later). `is_headquarters` marks HQ. | Admin |
| `projects` | Project sites: client, location, dates, status, lead division. `eoi_notice_id` can point to the EOI system. | Admin |
| `employees` | Personal and job details. Belongs to one department and one designation. | Admin / HR |
| `postings` | Where each employee is (or was) posted. Also the transfer history. | Admin |
| `users` | Login accounts. `employee_id` links a login to a staff record (optional). | Admin |
| `roles` | Roles with a JSON list of permissions, same pattern as the EOI system. | System admin |
| `activity_logs` | Who did what and when. Name is copied so the log survives user deletion. | System (automatic) |

## How a transfer works

An admin moves an employee from HQ to a project:

1. Inside one database transaction, the current posting (`end_date` empty) gets `end_date = transfer date`.
2. A new posting row is created: `posting_type = project`, `project_id = …`, `start_date = transfer date`, `end_date = null`.
3. An `activity_logs` row is written: `posting.created`.

Nothing is overwritten, so the full history stays in `postings`.

## Rules enforced in code

- One current posting per employee (checked in the transaction above).
- `hq` → `office_id` required, `project_id` must be null; `project` → the opposite.
- `end_date` must be on or after `start_date`.
- Divisions, departments, offices, projects and employees use **soft delete** (`deleted_at`) so history and old postings still resolve.
- A division or department that still has active employees cannot be deleted.

## Assumptions (please confirm)

1. Hierarchy is Division → Department → Employee, and the **posting** (HQ or Project) is attached to the employee — HQ and projects do not sit inside a department.
2. A project has one **lead division**, but staff from any department can be posted to it.
3. HQ is stored in an `offices` table, so more offices can be added later. If there is only ever one Head Office, the table simply has one row.
4. Only admins (a role with the right permission) create divisions, departments, offices, projects and postings.
