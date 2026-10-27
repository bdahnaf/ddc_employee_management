# Sign-in, roles and permissions

> **Currently switched off.** `config/workforce.php` has an `open_access` switch (env `WORKFORCE_OPEN_ACCESS`, default `true`).
> While it is on: no sign-in is needed, every route is open, and every gate below returns *allowed* (`Gate::before` in `AppServiceProvider`).
> Set `WORKFORCE_OPEN_ACCESS=false` in `.env` to bring back everything described on this page. The automated tests run with it off, so the rules stay tested.

How people sign in to DDC Workforce, what each role can do, and where that is decided in the code.

## Who can sign in

- Only DDC staff with an account. **There is no public sign-up**: HR creates every account.
- Sign-in is **email + password** (`/login`).
- A **deactivated** account cannot sign in. If HR deactivates someone who is already signed in, they are signed out on their next click.
- After **5 wrong passwords** for the same email from the same computer, sign-in is blocked for 60 seconds.
- "Keep me signed in on this computer" keeps the session after the browser closes.
- Forgotten passwords: HR sets a new one on the user's account page. Users can change their own password at **Change password**.

## Roles

| Role | Value in DB | Who | Can do |
|---|---|---|---|
| HR | `hr` | HR team | Everything: manage accounts, all records, sensitive details (salary, NID, TIN, bank, addresses) |
| Head | `head` | Division / department heads | Directory + basic profiles, plus attendance and leave **for their own team** |
| Employee | `employee` | All other staff | Directory + basic profiles, and **their own full profile** |

The role is stored in `users.role` and read through the `App\Enums\Role` enum.

### Safety rules

- There must always be **at least one active HR account**. The last HR cannot be demoted or deactivated.
- HR cannot deactivate their own account.
- Accounts are never deleted, only deactivated, so the activity log always refers to a real person.

## Permission names (gates)

Defined in `app/Providers/AppServiceProvider.php`. Routes use them as `->middleware('can:<name>')`, Blade views as `@can('<name>')`, and code as `$user->can('<name>')`.

| Gate | Allowed | Used for |
|---|---|---|
| `manage-users` | HR | `/hr/users` pages |
| `manage-records` | HR | Creating, editing and deleting divisions, departments, HQ, projects, designations, employees, postings |
| `view-sensitive` | HR, or the person themselves | Salary, NID, TIN, bank, addresses on a profile (`$user->can('view-sensitive', $employee)`) |
| `filter-sensitive` | HR | Directory filters and sorting by age or salary |
| `view-team-attendance` | HR, Head | Team attendance and leave (Heads only see their own team) |
| `view-activity` | HR | Activity log |

**Important:** these checks run on the server. Hiding a button in the page is only for convenience; the route and the API refuse the request anyway.

## Getting the first HR account

There is no HR account after a fresh install. Create one from the command line:

```bash
php artisan hr:create
# or
php artisan hr:create hr@ddclbd.com --name="HR Admin"
```

It asks for a password (at least 8 characters, letters and numbers). If the email already has an account, the command makes it an active HR account instead.

## Activity log

Every sign-in, sign-out and account change is written to `activity_logs` with who did it, when, and from which IP address:

```php
ActivityLog::record('user.created', "Created account for {$user->email}", $user);
```

| Action | When |
|---|---|
| `auth.login` / `auth.logout` | Sign in / out |
| `account.password` | User changed their own password |
| `user.created` / `user.updated` | HR created or edited an account (role changes and password resets are described) |
| `user.deactivated` / `user.reactivated` | HR switched an account off / on |

HR sees the latest entries on the dashboard.

## Files

```
app/Enums/Role.php                              the three roles and their labels
app/Models/User.php                             role, is_active, last_login_at, isHr(), isHead()
app/Models/ActivityLog.php                      ActivityLog::record(...)
app/Http/Controllers/Auth/LoginController.php   sign in / out, lock-out, inactive check
app/Http/Controllers/AccountController.php      change own password
app/Http/Controllers/DashboardController.php    first page after sign-in
app/Http/Controllers/Hr/UserController.php      HR: list, create, edit, deactivate accounts
app/Http/Middleware/EnsureUserIsActive.php      signs out deactivated users ("active" middleware)
app/Console/Commands/CreateHrUser.php           php artisan hr:create
app/Providers/AppServiceProvider.php            gates
bootstrap/app.php                               middleware alias + login redirects
routes/web.php                                  all routes
database/migrations/2026_10_15_000001_...       users.role, is_active, last_login_at
database/migrations/2026_10_15_000002_...       activity_logs
resources/views/layouts/panel.blade.php         signed-in layout (sidebar + top bar)
resources/views/auth/login.blade.php            sign-in page
resources/views/dashboard.blade.php
resources/views/account/password.blade.php
resources/views/hr/users/index.blade.php        accounts list with search and filters
resources/views/hr/users/form.blade.php         add / edit account
resources/css/panel.css, resources/js/panel.js  styles and small scripts for the signed-in area
tests/Feature/Auth/LoginTest.php                sign-in rules
tests/Feature/Hr/UserManagementTest.php         HR account management and access
tests/Feature/AccountTest.php                   dashboard, own password, hr:create
```

## Routes

| Method | URL | Who | What |
|---|---|---|---|
| GET / POST | `/login` | Guests | Sign-in page / sign in |
| POST | `/logout` | Signed in | Sign out |
| GET | `/directory` | Signed in | Staff directory (first page after sign-in) |
| GET | `/employees/{id}`, `/me` | Signed in | Profile / own profile |
| GET | `/dashboard` | HR | Overview (others are sent to the directory) |
| … | `/manage/...` | HR | Employees and organisation, see [directory.md](directory.md) |
| GET / PUT | `/account/password` | Signed in | Change own password |
| GET | `/hr/users` | HR | Accounts list (`?q=`, `?role=`, `?status=active\|inactive`) |
| GET / POST | `/hr/users/create`, `/hr/users` | HR | Add account |
| GET / PUT | `/hr/users/{id}/edit`, `/hr/users/{id}` | HR | Edit account, change role, reset password |
| PATCH | `/hr/users/{id}/toggle` | HR | Deactivate / reactivate |
