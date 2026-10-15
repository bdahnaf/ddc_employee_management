<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Posting;
use App\Models\Project;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * HR: add / edit employees, transfer them between HQ and projects, remove them.
 * All routes sit behind the `manage-records` gate (HR only).
 */
class EmployeeManageController extends Controller
{
    public function create(): View
    {
        return view('manage.employees.form', $this->formData(new Employee([
            'status' => 'active',
            'employment_type' => 'permanent',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            ...$this->rules(),
            'posting_type' => ['required', Rule::in([Posting::HQ, Posting::PROJECT])],
            'office_id' => ['required_if:posting_type,hq', 'nullable', 'exists:offices,id'],
            'project_id' => ['required_if:posting_type,project', 'nullable', 'exists:projects,id'],
            'role_on_posting' => ['nullable', 'string', 'max:120'],
            'posting_start' => ['required', 'date'],
            'order_ref' => ['nullable', 'string', 'max:60'],
        ], $this->messages());

        $employee = DB::transaction(function () use ($request, $data) {
            $employee = Employee::create($this->employeeColumns($data));

            if ($request->hasFile('photo')) {
                $employee->update(['photo_path' => $request->file('photo')->store('photos', 'local')]);
            }

            $employee->postings()->create([
                'posting_type' => $data['posting_type'],
                'office_id' => $data['posting_type'] === Posting::HQ ? $data['office_id'] : null,
                'project_id' => $data['posting_type'] === Posting::PROJECT ? $data['project_id'] : null,
                'role_on_posting' => $data['role_on_posting'] ?? null,
                'start_date' => $data['posting_start'],
                'order_ref' => $data['order_ref'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $this->saveSalary($employee, $data, $request->user()?->id);

            return $employee;
        });

        ActivityLog::record('employee.created', "Added employee {$employee->name} ({$employee->employee_code})", $employee);

        return redirect()->route('employees.show', $employee)->with('status', "{$employee->name} has been added.");
    }

    public function edit(Employee $employee): View
    {
        $employee->load('currentSalary');

        return view('manage.employees.form', $this->formData($employee));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate($this->rules($employee), $this->messages());

        DB::transaction(function () use ($request, $employee, $data) {
            $employee->update($this->employeeColumns($data));

            if ($request->boolean('remove_photo') && $employee->photo_path) {
                Storage::disk('local')->delete($employee->photo_path);
                $employee->update(['photo_path' => null]);
            }
            if ($request->hasFile('photo')) {
                if ($employee->photo_path) {
                    Storage::disk('local')->delete($employee->photo_path);
                }
                $employee->update(['photo_path' => $request->file('photo')->store('photos', 'local')]);
            }

            $this->saveSalary($employee, $data, $request->user()?->id);
        });

        ActivityLog::record('employee.updated', "Updated {$employee->name} ({$employee->employee_code})", $employee);

        return redirect()->route('employees.show', $employee)->with('status', 'Changes saved.');
    }

    /**
     * Move someone between HQ and a project (or between projects).
     * Closes the current posting the day before the new one starts.
     */
    public function transfer(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'posting_type' => ['required', Rule::in([Posting::HQ, Posting::PROJECT])],
            'office_id' => ['required_if:posting_type,hq', 'nullable', 'exists:offices,id'],
            'project_id' => ['required_if:posting_type,project', 'nullable', 'exists:projects,id'],
            'role_on_posting' => ['nullable', 'string', 'max:120'],
            'start_date' => ['required', 'date'],
            'order_ref' => ['nullable', 'string', 'max:60'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], $this->messages());

        $new = DB::transaction(function () use ($request, $employee, $data) {
            $current = $employee->postings()->whereNull('end_date')->lockForUpdate()->first();
            $start = Carbon::parse($data['start_date']);

            if ($current) {
                if ($start->lte($current->start_date)) {
                    throw ValidationException::withMessages([
                        'start_date' => 'The new posting must start after the current one started ('.$current->start_date->format('d M Y').').',
                    ]);
                }
                $current->update(['end_date' => $start->copy()->subDay()]);
            }

            return $employee->postings()->create([
                'posting_type' => $data['posting_type'],
                'office_id' => $data['posting_type'] === Posting::HQ ? $data['office_id'] : null,
                'project_id' => $data['posting_type'] === Posting::PROJECT ? $data['project_id'] : null,
                'role_on_posting' => $data['role_on_posting'] ?? null,
                'start_date' => $start,
                'order_ref' => $data['order_ref'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $request->user()?->id,
            ]);
        });

        $new->load('office', 'project');
        ActivityLog::record('posting.created', "Transferred {$employee->name} to {$new->placeName()} from {$new->start_date->format('d M Y')}", $employee);

        return redirect()->route('employees.show', $employee)->with('status', "{$employee->name} is now posted at {$new->placeName()}.");
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete(); // soft delete: postings and history stay in the database
        ActivityLog::record('employee.deleted', "Removed {$employee->name} ({$employee->employee_code})", $employee);

        return redirect()->route('directory')->with('status', "{$employee->name} has been removed from the directory.");
    }

    // ---------------------------------------------------------------

    private function rules(?Employee $employee = null): array
    {
        $id = $employee?->id;

        return [
            'employee_code' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_code')->ignore($id)],
            'name' => ['required', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'status' => ['required', Rule::in(array_keys(Employee::STATUSES))],
            'employment_type' => ['required', Rule::in(array_keys(Employee::EMPLOYMENT_TYPES))],
            'joining_date' => ['nullable', 'date'],
            'office_email' => ['nullable', 'email', 'max:190'],
            'office_phone' => ['nullable', 'string', 'max:40'],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($id)],

            'personal_email' => ['nullable', 'email', 'max:190'],
            'personal_phone' => ['nullable', 'string', 'max:40'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(array_keys(Employee::GENDERS))],
            'blood_group' => ['nullable', Rule::in(Employee::BLOOD_GROUPS)],
            'nid_no' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'nid_no')->ignore($id)],
            'tin_no' => ['nullable', 'string', 'max:30'],
            'passport_no' => ['nullable', 'string', 'max:30'],
            'present_address' => ['nullable', 'string', 'max:500'],
            'permanent_address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:60'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_account_no' => ['nullable', 'string', 'max:60'],

            'basic' => ['nullable', 'numeric', 'min:0'],
            'house_rent' => ['nullable', 'numeric', 'min:0'],
            'medical' => ['nullable', 'numeric', 'min:0'],
            'conveyance' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'salary_from' => ['nullable', 'date'],
        ];
    }

    private function messages(): array
    {
        return [
            'user_id.unique' => 'That sign-in account is already linked to another employee.',
            'office_id.required_if' => 'Choose which office.',
            'project_id.required_if' => 'Choose which project.',
            'department_id.required' => 'Choose a department.',
        ];
    }

    private function employeeColumns(array $data): array
    {
        return collect($data)->only([
            'employee_code', 'name', 'designation_id', 'department_id', 'status', 'employment_type',
            'joining_date', 'office_email', 'office_phone', 'user_id', ...Employee::SENSITIVE,
        ])->all();
    }

    /**
     * Saves a new salary row when the amounts changed. The previous row is closed
     * the day before, so salary history (increments) is kept.
     */
    private function saveSalary(Employee $employee, array $data, ?int $by): void
    {
        $parts = collect(SalaryStructure::PARTS)->keys()->mapWithKeys(fn ($k) => [$k => (float) ($data[$k] ?? 0)])->all();
        $gross = SalaryStructure::grossOf($parts);
        $current = $employee->currentSalary()->first();

        if (! $current && $gross <= 0) {
            return; // no salary entered
        }
        if ($current && collect($parts)->every(fn ($v, $k) => (float) $current->{$k} === $v)) {
            return; // unchanged
        }

        // First salary starts on the joining date; a change starts today unless HR gives a date.
        $default = $current ? now() : ($employee->joining_date ?? now());
        $from = Carbon::parse($data['salary_from'] ?? $default)->startOfDay();

        if ($current) {
            if ($from->lte($current->effective_from)) {
                // Correction to the current salary (not an increment): overwrite it.
                $current->update([...$parts, 'gross' => $gross]);

                return;
            }
            $current->update(['effective_to' => $from->copy()->subDay()]);
        }

        $employee->salaries()->create([...$parts, 'gross' => $gross, 'effective_from' => $from, 'created_by' => $by]);
    }

    private function formData(Employee $employee): array
    {
        return [
            'employee' => $employee,
            'departments' => Department::with('division:id,name')->orderBy('name')->get(),
            'designations' => Designation::orderBy('title')->get(),
            'offices' => Office::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'accounts' => User::whereDoesntHave('employee')->orWhere('id', $employee->user_id)->orderBy('name')->get(['id', 'name', 'email']),
        ];
    }
}
