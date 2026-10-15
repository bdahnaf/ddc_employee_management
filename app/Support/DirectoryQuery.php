<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Builds the staff directory list from the request.
 *
 *   ?place=division:3 | department:7 | hq | office:1 | project:12   which group to show
 *   ?q=                     name or employee ID
 *   ?designation=, ?status=
 *   ?sort=name|joined       everyone
 *   ?age_min=, ?age_max=, ?salary_min=, ?salary_max=, ?sort=salary|age   HR only
 *
 * The HR-only filters are dropped for everyone else (not just hidden in the page),
 * so a regular employee can never use them to work out someone's age or salary.
 */
class DirectoryQuery
{
    public const PER_PAGE = 24;

    public function __construct(private Request $request, private ?User $viewer = null) {}

    public function canUseSensitive(): bool
    {
        return Gate::forUser($this->viewer)->allows('filter-sensitive');
    }

    /** The filters actually applied (HR-only ones removed for non-HR). */
    public function filters(): array
    {
        $f = [
            'place' => (string) $this->request->query('place', ''),
            'q' => trim((string) $this->request->query('q', '')),
            'designation' => $this->int('designation'),
            'status' => (string) $this->request->query('status', 'active'),
            'sort' => (string) $this->request->query('sort', 'name'),
        ];

        if ($this->canUseSensitive()) {
            $f += [
                'age_min' => $this->int('age_min'),
                'age_max' => $this->int('age_max'),
                'salary_min' => $this->int('salary_min'),
                'salary_max' => $this->int('salary_max'),
            ];
        } elseif (in_array($f['sort'], ['salary', 'age'], true)) {
            $f['sort'] = 'name';
        }

        return $f;
    }

    public function paginate(): LengthAwarePaginator
    {
        return $this->build()->paginate(self::PER_PAGE)->withQueryString();
    }

    public function build(): Builder
    {
        $f = $this->filters();

        $query = Employee::query()
            ->select('employees.*')
            ->with(['designation', 'department.division', 'currentPosting.office', 'currentPosting.project']);

        $this->applyPlace($query, $f['place']);

        $query
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('employees.name', 'like', "%{$f['q']}%")
                ->orWhere('employees.employee_code', 'like', "%{$f['q']}%")))
            ->when($f['designation'], fn ($q, $id) => $q->where('employees.designation_id', $id))
            ->when($f['status'] !== 'all' && array_key_exists($f['status'], Employee::STATUSES),
                fn ($q) => $q->where('employees.status', $f['status']));

        if ($this->canUseSensitive()) {
            $this->applySensitive($query, $f);
        }

        return match ($f['sort']) {
            'joined' => $query->orderByDesc('employees.joining_date')->orderBy('employees.name'),
            'age' => $query->orderBy('employees.date_of_birth')->orderBy('employees.name'),   // oldest first
            'salary' => $query->orderByDesc($this->salarySubquery())->orderBy('employees.name'),
            default => $query->orderBy('employees.name'),
        };
    }

    /** Label and record for the chosen place, for the page heading. */
    public function placeSummary(): array
    {
        [$type, $id] = array_pad(explode(':', $this->filters()['place'], 2), 2, null);
        $id = $id !== null ? (int) $id : null;

        return match ($type) {
            'division' => ['type' => 'Division', 'model' => Division::find($id)],
            'department' => ['type' => 'Department', 'model' => Department::with('division')->find($id)],
            'office' => ['type' => 'Head Office', 'model' => Office::find($id)],
            'hq' => ['type' => 'Head Office', 'model' => null],
            'project' => ['type' => 'Project', 'model' => Project::find($id)],
            'projects' => ['type' => 'All projects', 'model' => null],
            default => ['type' => null, 'model' => null],
        };
    }

    private function applyPlace(Builder $query, string $place): void
    {
        [$type, $id] = array_pad(explode(':', $place, 2), 2, null);
        $id = is_numeric($id) ? (int) $id : null;

        match ($type) {
            'division' => $id ? $query->inDivision($id) : null,
            'department' => $id ? $query->where('employees.department_id', $id) : null,
            'hq' => $query->atHq(),
            'office' => $query->atHq($id),
            'projects' => $query->onProject(),
            'project' => $query->onProject($id),
            default => null,
        };
    }

    private function applySensitive(Builder $query, array $f): void
    {
        // Age from date of birth: older than N years = born on/before today minus N years.
        if ($f['age_min']) {
            $query->whereDate('employees.date_of_birth', '<=', now()->subYears($f['age_min'])->toDateString());
        }
        if ($f['age_max']) {
            $query->whereDate('employees.date_of_birth', '>', now()->subYears($f['age_max'] + 1)->toDateString());
        }
        if ($f['salary_min'] || $f['salary_max']) {
            $query->whereHas('currentSalary', function ($s) use ($f) {
                if ($f['salary_min']) {
                    $s->where('gross', '>=', $f['salary_min']);
                }
                if ($f['salary_max']) {
                    $s->where('gross', '<=', $f['salary_max']);
                }
            });
        }
    }

    private function salarySubquery()
    {
        return \App\Models\SalaryStructure::select('gross')
            ->whereColumn('salary_structures.employee_id', 'employees.id')
            ->whereNull('effective_to')
            ->orderByDesc('effective_from')
            ->limit(1);
    }

    private function int(string $key): ?int
    {
        $v = $this->request->query($key);

        return is_numeric($v) && (int) $v > 0 ? (int) $v : null;
    }
}
