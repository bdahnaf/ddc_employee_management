<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Posting;
use App\Models\Project;
use App\Support\DirectoryQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The staff directory: pick a division, department, Head Office or project
 * and see everyone working there as photo cards. Open to every signed-in user;
 * sensitive filters are HR-only (see DirectoryQuery).
 */
class DirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $directory = new DirectoryQuery($request, $request->user());

        return view('directory.index', [
            'employees' => $directory->paginate(),
            'filters' => $directory->filters(),
            'place' => $directory->placeSummary(),
            'canUseSensitive' => $directory->canUseSensitive(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::with('division:id,name')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'division_id']),
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'projects' => Project::orderByRaw("CASE status WHEN 'ongoing' THEN 0 WHEN 'planned' THEN 1 ELSE 2 END")->orderBy('name')->get(['id', 'name', 'status']),
            'designations' => Designation::orderBy('title')->get(['id', 'title']),
            'statuses' => Employee::STATUSES,
            'counts' => [
                'all' => Employee::where('status', 'active')->count(),
                'hq' => Posting::current()->where('posting_type', Posting::HQ)->whereHas('employee', fn ($e) => $e->where('status', 'active'))->count(),
                'project' => Posting::current()->where('posting_type', Posting::PROJECT)->whereHas('employee', fn ($e) => $e->where('status', 'active'))->count(),
            ],
        ]);
    }
}
