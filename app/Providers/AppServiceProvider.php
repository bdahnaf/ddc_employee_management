<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Gates: one name per kind of action. Routes use them as `can:<name>`,
     * views as @can('<name>'). See docs/auth.md for the full table.
     */
    public function boot(): void
    {
        // Open access (config/workforce.php): every permission is granted, signed in or not.
        // Remove nothing below: these rules apply again when WORKFORCE_OPEN_ACCESS=false.
        Gate::before(fn (?User $user) => config('workforce.open_access') ? true : null);

        // HR manages sign-in accounts.
        Gate::define('manage-users', fn (User $user) => $user->isHr());

        // HR creates / edits / deletes divisions, departments, HQ, projects, employees, postings.
        Gate::define('manage-records', fn (User $user) => $user->isHr());

        // Sensitive personal data (salary, NID, TIN, bank, addresses…): HR, or the person themselves.
        Gate::define('view-sensitive', fn (User $user, Employee $employee) => $user->isHr() || ($employee->user_id !== null && $employee->user_id === $user->id));

        // Directory filters / sorting by age or salary would reveal private data, so HR only.
        Gate::define('filter-sensitive', fn (User $user) => $user->isHr());

        // Team attendance and leave: HR, or a division / department head (scoped to their team in the query).
        Gate::define('view-team-attendance', fn (User $user) => $user->isHr() || $user->isHead());

        // Activity log.
        Gate::define('view-activity', fn (User $user) => $user->isHr());
    }
}
