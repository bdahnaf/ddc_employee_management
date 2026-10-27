<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Posting;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * HR overview (Manage → Overview): headcount, where people are posted, recent activity.
 * Everyone else is sent to the staff directory.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! Gate::allows('manage-records')) {
            return redirect()->route('directory');
        }

        $active = fn ($q) => $q->where('status', 'active');

        return view('dashboard', [
            'user' => $user,
            'counts' => [
                'employees' => Employee::where('status', 'active')->count(),
                'hq' => Posting::current()->where('posting_type', Posting::HQ)->whereHas('employee', $active)->count(),
                'project' => Posting::current()->where('posting_type', Posting::PROJECT)->whereHas('employee', $active)->count(),
                'projects' => Project::where('status', 'ongoing')->count(),
                'divisions' => Division::count(),
                'departments' => Department::count(),
                'accounts' => User::active()->count(),
                'heads' => User::where('role', Role::Head)->count(),
            ],
            'recentTransfers' => Posting::with(['employee', 'office', 'project'])->latest('created_at')->limit(5)->get(),
            'activity' => ActivityLog::latest('created_at')->limit(8)->get(),
        ]);
    }
}
