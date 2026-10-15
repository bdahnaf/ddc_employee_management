# Staff directory, profiles and HR management

The main part of DDC Workforce. Everyone who signs in lands on the **staff directory**.

## Who sees what

> While open access is on (see [auth.md](auth.md)), everyone sees everything in the right-hand columns as if they were HR. The table describes the rules once sign-in is switched back on.

| | HR | Head | Employee |
|---|---|---|---|
| Directory cards, search, filter by place / position / status | ✓ | ✓ | ✓ |
| Filter or sort by **age** or **salary** | ✓ | – | – |
| Profile: job details, postings, office email / phone | ✓ | ✓ | ✓ |
| Profile: personal details, NID, TIN, passport, addresses, bank, salary | ✓ | own only | own only |
| Add / edit / transfer / remove employees | ✓ | – | – |
| Divisions, departments, Head Office, projects, designations | ✓ | – | – |

These rules are checked **on the server**:

- `view-sensitive` gate (`AppServiceProvider`): HR, or the user linked to that employee record (`employees.user_id`). The profile view only renders private sections when it passes, so the data is never sent to the browser otherwise.
- `filter-sensitive` gate: HR only. `App\Support\DirectoryQuery` drops `age_min`, `age_max`, `salary_min`, `salary_max` and `sort=salary|age` for everyone else, so these filters can't be used to work out someone's salary or age.
- `manage-records` gate: HR only, on every `/manage/...` route.
- Photos are stored in `storage/app/private/photos` (not public) and served by `/employees/{id}/photo` to signed-in users only.

Tests: `tests/Feature/DirectoryTest.php` checks each of these, e.g. that a regular employee asking for `?salary_min=100000` still sees everyone.

## Directory (`/directory`)

Choose where to look with `?place=`:

| `place` | Shows |
|---|---|
| *(empty)* | Everyone |
| `hq` | Everyone whose **current** posting is at an office |
| `office:{id}` | One office |
| `projects` | Everyone currently on any project |
| `project:{id}` | One project's team |
| `division:{id}` | Everyone in that division's departments |
| `department:{id}` | One department |

Other filters: `q` (name or employee ID), `designation`, `status` (`active` by default, or `all`), `sort` (`name`, `joined`).
HR only: `age_min`, `age_max`, `salary_min`, `salary_max` (gross per month), `sort=salary|age`.

"Current posting" = the posting row with no `end_date`.

## Profile (`/employees/{id}`)

Header with photo, designation, department and division, current posting, employee ID, status and length of service. Then four sections: **Job**, **Postings** (full history), **Personal details** and **Salary** (private). `/me` opens the signed-in user's own profile; if their account isn't linked to a staff record it says to ask HR.

## HR: managing employees (`Manage` menu)

| Action | Where | Notes |
|---|---|---|
| Add employee | Manage → Add employee | Basic info, photo, first posting (HQ or project), salary, personal details. Only name, employee ID and department are required. |
| Edit | **Edit** on the profile | Changing salary amounts **adds a new salary row** from the chosen date and closes the old one, so increments are kept. A date on or before the current salary's start corrects it instead. |
| Transfer | **Transfer** on the profile | Ends the current posting the day before the new one starts and adds the new one. Can't start on or before the current posting's start date. |
| Remove | Bottom of the profile | Soft delete: hidden from the directory, but postings, salary history and the activity log stay in the database. |
| Link to a sign-in account | "Sign-in account" field on the form | Lets that person see their own private details. |

Every change is written to the activity log (`employee.created`, `employee.updated`, `posting.created`, `employee.deleted`).

## HR: organisation (`/manage/{type}`)

One set of pages for `divisions`, `departments`, `offices` (Head Office), `projects` and `designations`, configured in `App\Support\StructureTypes` (fields, validation, table columns, and what blocks deletion).

You can't remove something that is still in use:

- a division that still has departments
- a department that still has employees
- an office or project with staff currently posted there
- a designation someone still holds

Clicking a division, department, office or project name in its list opens the directory filtered to its people.

## Dummy data

```bash
php artisan db:seed                 # or: php artisan migrate:fresh --seed
php artisan demo:data --fresh       # re-create it
php artisan demo:data --remove      # delete it; real records are not touched
```

Adds DDC's 7 sectors as divisions, 14 departments, Head Office, 11 projects (ongoing, planned and completed), 16 designations and 60 made-up employees with personal details, 1–3 postings each (HQ ↔ project moves), salary increments, and a few on leave, resigned or retired. Every dummy code starts with `DEMO-`.

## Files

```
app/Models/Division.php, Department.php, Designation.php, Office.php, Project.php
app/Models/Employee.php          SENSITIVE fields list, currentPosting, currentSalary, directory scopes
app/Models/Posting.php           HQ / project posting, end_date NULL = current
app/Models/SalaryStructure.php   pay parts + gross, effective_to NULL = current
app/Support/DirectoryQuery.php   directory filters (HR-only ones dropped for others)
app/Support/StructureTypes.php   config for the organisation pages
app/Http/Controllers/DirectoryController.php
app/Http/Controllers/EmployeeController.php          profile, /me, photo
app/Http/Controllers/Manage/EmployeeManageController.php  add / edit / transfer / remove
app/Http/Controllers/Manage/StructureController.php       organisation pages
app/Console/Commands/DemoData.php                     php artisan demo:data
database/migrations/2026_10_15_000003_create_organisation_tables.php
database/migrations/2026_10_15_000004_create_employee_tables.php
resources/views/layouts/panel.blade.php     shared signed-in layout (top bar)
resources/views/directory/index.blade.php   cards + filters
resources/views/employees/show.blade.php    profile + transfer dialog
resources/views/manage/employees/           add / edit form, posting fields
resources/views/manage/structure/           organisation list + form
tests/Feature/DirectoryTest.php, tests/Feature/Manage/*
```
