# DDC Workforce

Employee management system for **Development Design Consultants Limited (DDC)**.

It keeps one staff register for the whole firm: which **division** and **department** each person belongs to, and whether they are posted at **Head Office (HQ)** or on a **project**. Everything is exposed through a REST API documented with Swagger, so other DDC systems (such as the EOI management system) can use the same data.

> Status: early development. Homepage, sign-in, the staff directory with photo cards, employee profiles, transfers, salary history and HR management of divisions, departments, Head Office and projects are done. The REST API + Swagger and attendance are next.

---

## Organisation model

```
DDC
 └── Division            (e.g. Building & Planning, Roads & Bridges, ICT)
      └── Department     (e.g. Architecture, Structural)
           └── Employee
                └── Posting  →  HQ  or  Project
```

- A **division** has many departments.
- A **department** has many employees.
- An **employee** has exactly one current **posting**, either HQ or a project. Earlier postings are kept as history (transfers).
- Divisions, departments, projects, HQ and employees each support full CRUD (create, read, update, delete) through the API.

## Open access (for now)

While the permission rules are being decided, **sign-in is switched off**: every page, the Manage menu and all private details are open to anyone who opens the site. The top bar shows an "Open access" badge.

Everything for sign-in and roles is still in the code. To switch it back on, add this to `.env`:

```
WORKFORCE_OPEN_ACCESS=false
```

## Who uses it (once sign-in is on)

Only DDC staff, with an email + password account created by HR (no public sign-up).

| Role | Sees |
|---|---|
| **HR** | Everything, including salary, NID, TIN, bank and personal details. Manages accounts and all records. |
| **Head** (division / department head) | Staff directory and basic profiles, plus attendance and leave for their own team. |
| **Employee** | Staff directory and basic profiles, plus their own full profile. |

Details: [docs/auth.md](docs/auth.md) (sign-in and roles) and [docs/directory.md](docs/directory.md) (directory, profiles, what is private).

## Main pages

| Page | URL | Who |
|---|---|---|
| Homepage with links into the app | `/` | Everyone |
| Staff directory (photo cards, filter by division / department / Head Office / project) | `/directory` | Signed in |
| Employee profile | `/employees/{id}` | Signed in (private sections: HR + the person) |
| My profile | `/me` | Signed in |
| HR overview | `/dashboard` | HR |
| Add / edit employees, transfers | Manage menu | HR |
| Divisions, departments, Head Office, projects, designations | `/manage/{type}` | HR |
| Sign-in accounts | `/hr/users` | HR |

## Tech stack

| Part | Choice |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Database | SQLite in development (`database/database.sqlite`) |
| Frontend build | Vite |
| API docs | Swagger / OpenAPI (planned: `/api/documentation`) |

## Getting started

```bash
# 1. Install PHP and JS dependencies
composer install
npm install

# 2. Environment
cp .env.example .env        # skip if .env already exists
php artisan key:generate

# 3. Database + dummy data (60 made-up employees, projects, divisions…)
php artisan migrate:fresh --seed

# 4. Run (two terminals)
php artisan serve           # http://localhost:8000
npm run dev                 # Vite: CSS/JS with hot reload
```

Open http://localhost:8000 and click **Open staff directory**. No sign-in is needed while open access is on.

Dummy data: `php artisan demo:data --fresh` re-creates it, `php artisan demo:data --remove` deletes it (real records are kept). All dummy codes start with `DEMO-`.

When sign-in is switched back on, create the first HR account with `php artisan hr:create hr@ddclbd.com --name="HR Admin"`.

For a production build of the assets: `npm run build`.

## Tests

```bash
php artisan test
```

Covers sign-in rules, HR-only access, the directory filters, privacy (regular employees never get salary, NID, TIN, etc., and can't filter by them), transfers, salary history and the organisation pages.

## Project layout

```
app/Enums/Role.php                    hr / head / employee
app/Models/                           User, ActivityLog, Division, Department, Designation, Office, Project, Employee, Posting, SalaryStructure
app/Support/                          DirectoryQuery (directory filters), StructureTypes (organisation pages)
app/Http/Controllers/Auth/            sign in / out
app/Http/Controllers/                 Directory, Employee (profile), Dashboard, Account
app/Http/Controllers/Manage/          HR: employees, transfers, organisation
app/Http/Controllers/Hr/              HR: sign-in accounts
app/Http/Middleware/                  signs out deactivated users
app/Console/Commands/                 php artisan hr:create, php artisan demo:data
app/Providers/AppServiceProvider.php  permission rules (gates)
routes/web.php                        all web routes
database/migrations/                  tables
resources/views/home.blade.php        public homepage
resources/views/auth/                 sign-in page
resources/views/layouts/panel.blade.php  signed-in layout (sidebar + top bar)
resources/views/directory/, employees/  directory cards, profile
resources/views/manage/, hr/          HR pages
resources/css/home.css, panel.css     styles (homepage / signed-in area)
resources/js/home.js, panel.js        small scripts
tests/Feature/                        automated tests
docs/auth.md                          sign-in, roles, permissions
docs/directory.md                     directory, profiles, privacy rules, HR management
docs/database/                        ER diagram (dot/png/svg/pdf) and table notes
```

## Design notes

The homepage follows the look of [ddclbd.com](https://www.ddclbd.com/):

| Token | Hex | Use |
|---|---|---|
| `--maroon` / `--maroon-dark` | `#8e2219` / `#6e1812` | logo block, feature band, sector hover |
| `--brick` | `#b54d3d` | pill buttons and accents (same as DDC's buttons) |
| `--head` / `--text` | `#444444` / `#555555` | headings / body text |
| `--soft` / white | `#f9f9f9` / `#ffffff` | section backgrounds |
| `--green` / `--amber` / `--leave` | `#3d8b5f` / `#d59a2a` / `#5f7bb0` | project posting, late, leave |

Fonts (same as ddclbd.com): **Arimo** for headings, **Lato** for text, **Rubik** for uppercase pill buttons.

Animations are kept light: a line drawing in the hero, gentle fade-in on scroll, the structure line filling in,
and the sample profile switching between Project and HQ. All respect the *reduce motion* setting.
The homepage uses placeholder labels and bars instead of invented names or numbers.

## Roadmap

- [x] Homepage
- [x] ER diagram, draft 1 (`docs/database/`)
- [x] Database: divisions, departments, designations, offices, projects, employees, postings, salary
- [x] Staff directory with photo cards and filters, employee profiles with privacy rules
- [x] HR: add / edit / transfer / remove employees; manage divisions, departments, Head Office, projects, designations
- [ ] REST API (`/api/v1/...`) with CRUD for each resource
- [ ] Swagger / OpenAPI documentation
- [ ] Attendance and leave
- [ ] Payroll
- [x] Login (email + password), roles (HR / Head / Employee), HR account management, activity log
- [ ] Connection to the EOI management system
- [ ] Code guide in `docs/`
