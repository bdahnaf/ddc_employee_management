# DDC Workforce

Employee management system for **Development Design Consultants Limited (DDC)**.

It keeps one staff register for the whole firm: which **division** and **department** each person belongs to, and whether they are posted at **Head Office (HQ)** or on a **project**. Everything is exposed through a REST API documented with Swagger, so other DDC systems (such as the EOI management system) can use the same data.

> Status: early development. The homepage is done. The data model, API and Swagger docs are next.

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

# 3. Database
php artisan migrate

# 4. Run (two terminals)
php artisan serve           # http://localhost:8000
npm run dev                 # Vite: CSS/JS with hot reload
```

For a production build of the assets: `npm run build`.

## Project layout

```
routes/web.php                  web pages ( / → home )
resources/views/home.blade.php  public homepage
resources/css/home.css          homepage styles (brand colours as CSS variables)
resources/js/home.js            homepage behaviour (scroll reveal, menu, posting demo)
vite.config.js                  asset entry points
docs/database/                  ER diagram (dot/png/svg/pdf) and table notes
```

More folders (API controllers, models, migrations, docs) will be listed here as they are added.

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
- [ ] Database: divisions, departments, projects, employees, postings
- [ ] REST API (`/api/v1/...`) with CRUD for each resource
- [ ] Swagger / OpenAPI documentation
- [ ] Login and roles
- [ ] Connection to the EOI management system
- [ ] Code guide in `docs/`
