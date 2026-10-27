<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Employee profile pages, visible to every signed-in user.
 * Sensitive sections only render when the `view-sensitive` gate allows
 * (HR, or the employee looking at their own profile).
 */
class EmployeeController extends Controller
{
    public function show(Request $request, Employee $employee): View
    {
        $employee->load(['designation', 'department.division', 'currentPosting.office', 'currentPosting.project', 'postings.office', 'postings.project', 'user']);

        $canSeeSensitive = Gate::allows('view-sensitive', $employee);

        if ($canSeeSensitive) {
            $employee->load(['currentSalary', 'salaries']);
        }

        return view('employees.show', [
            'employee' => $employee,
            'canSeeSensitive' => $canSeeSensitive,
            'isSelf' => $request->user() !== null && $employee->user_id === $request->user()->id,
        ]);
    }

    /** "My profile": the signed-in user's own staff record. */
    public function me(Request $request): RedirectResponse|View
    {
        $employee = $request->user()?->employee;

        if ($employee) {
            return redirect()->route('employees.show', $employee);
        }

        return view('employees.no-profile');
    }

    /**
     * Photos are stored privately (not in /public) and only served to signed-in users.
     */
    public function photo(Employee $employee): Response
    {
        abort_unless($employee->photo_path && Storage::disk('local')->exists($employee->photo_path), 404);

        return Storage::disk('local')->response($employee->photo_path, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
