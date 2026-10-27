<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Division;
use App\Models\Office;
use App\Models\Project;
use Illuminate\Validation\Rule;

/**
 * Describes the simple "organisation" records HR manages with one shared set of pages:
 * divisions, departments, Head Office, projects and designations.
 *
 * Each type lists its model, its form fields, its validation rules, the columns
 * shown in the table, and what blocks deletion. Adding a field = one line here.
 */
class StructureTypes
{
    public static function all(): array
    {
        return [
            'divisions' => [
                'model' => Division::class,
                'singular' => 'division',
                'title' => 'Divisions',
                'help' => 'The firm\'s main business units. Departments sit inside a division.',
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                    'code' => ['label' => 'Short code', 'type' => 'text', 'hint' => 'Optional, e.g. RB'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
                ],
                'rules' => fn ($id) => [
                    'name' => ['required', 'string', 'max:150', Rule::unique('divisions', 'name')->ignore($id)->withoutTrashed()],
                    'code' => ['nullable', 'string', 'max:30', Rule::unique('divisions', 'code')->ignore($id)],
                    'description' => ['nullable', 'string', 'max:1000'],
                    'is_active' => ['boolean'],
                ],
                'columns' => ['name' => 'Name', 'code' => 'Code', 'departments_count' => 'Departments', 'is_active' => 'Status'],
                'with_count' => ['departments'],
                'directory' => 'division',
                'blocked_by' => fn ($m) => $m->departments()->exists() ? 'It still has departments. Move or remove them first.' : null,
            ],

            'departments' => [
                'model' => Department::class,
                'singular' => 'department',
                'title' => 'Departments',
                'help' => 'Teams inside a division. Every employee belongs to one department.',
                'fields' => [
                    'division_id' => ['label' => 'Division', 'type' => 'select', 'required' => true, 'options' => fn () => Division::orderBy('name')->pluck('name', 'id')],
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                    'code' => ['label' => 'Short code', 'type' => 'text'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
                ],
                'rules' => fn ($id) => [
                    'division_id' => ['required', 'exists:divisions,id'],
                    'name' => ['required', 'string', 'max:150', Rule::unique('departments', 'name')->ignore($id)->where('division_id', request('division_id'))->withoutTrashed()],
                    'code' => ['nullable', 'string', 'max:30', Rule::unique('departments', 'code')->ignore($id)],
                    'description' => ['nullable', 'string', 'max:1000'],
                    'is_active' => ['boolean'],
                ],
                'columns' => ['name' => 'Name', 'division.name' => 'Division', 'employees_count' => 'Staff', 'is_active' => 'Status'],
                'with' => ['division'],
                'with_count' => ['employees'],
                'directory' => 'department',
                'blocked_by' => fn ($m) => $m->employees()->exists() ? 'Employees still belong to it. Move them to another department first.' : null,
            ],

            'offices' => [
                'model' => Office::class,
                'singular' => 'office',
                'title' => 'Head Office',
                'help' => 'Where HQ staff are posted. Usually just the Head Office.',
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'hint' => 'e.g. Head Office'],
                    'code' => ['label' => 'Short code', 'type' => 'text'],
                    'city' => ['label' => 'City', 'type' => 'text'],
                    'address' => ['label' => 'Address', 'type' => 'textarea'],
                    'is_headquarters' => ['label' => 'This is the headquarters', 'type' => 'checkbox'],
                ],
                'rules' => fn ($id) => [
                    'name' => ['required', 'string', 'max:150'],
                    'code' => ['nullable', 'string', 'max:30', Rule::unique('offices', 'code')->ignore($id)],
                    'city' => ['nullable', 'string', 'max:100'],
                    'address' => ['nullable', 'string', 'max:1000'],
                    'is_headquarters' => ['boolean'],
                ],
                'columns' => ['name' => 'Name', 'city' => 'City', 'current_count' => 'Staff here now'],
                'current_count' => 'office_id',
                'directory' => 'office',
                'blocked_by' => fn ($m) => $m->postings()->whereNull('end_date')->exists() ? 'Staff are currently posted here. Transfer them first.' : null,
            ],

            'projects' => [
                'model' => Project::class,
                'singular' => 'project',
                'title' => 'Projects',
                'help' => 'Project sites staff can be posted to. Led by one division.',
                'fields' => [
                    'name' => ['label' => 'Project name', 'type' => 'text', 'required' => true],
                    'code' => ['label' => 'Project code', 'type' => 'text'],
                    'division_id' => ['label' => 'Lead division', 'type' => 'select', 'options' => fn () => Division::orderBy('name')->pluck('name', 'id')],
                    'client' => ['label' => 'Client', 'type' => 'text'],
                    'location' => ['label' => 'Location', 'type' => 'text'],
                    'start_date' => ['label' => 'Start date', 'type' => 'date'],
                    'end_date' => ['label' => 'End date', 'type' => 'date'],
                    'status' => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => fn () => Project::STATUSES],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                ],
                'rules' => fn ($id) => [
                    'name' => ['required', 'string', 'max:200'],
                    'code' => ['nullable', 'string', 'max:30', Rule::unique('projects', 'code')->ignore($id)],
                    'division_id' => ['nullable', 'exists:divisions,id'],
                    'client' => ['nullable', 'string', 'max:200'],
                    'location' => ['nullable', 'string', 'max:200'],
                    'start_date' => ['nullable', 'date'],
                    'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                    'status' => ['required', Rule::in(array_keys(Project::STATUSES))],
                    'description' => ['nullable', 'string', 'max:2000'],
                ],
                'columns' => ['name' => 'Project', 'division.name' => 'Lead division', 'status' => 'Status', 'current_count' => 'Staff on site'],
                'with' => ['division'],
                'current_count' => 'project_id',
                'directory' => 'project',
                'blocked_by' => fn ($m) => $m->postings()->whereNull('end_date')->exists() ? 'Staff are currently posted to this project. Transfer them first.' : null,
            ],

            'designations' => [
                'model' => Designation::class,
                'singular' => 'designation',
                'title' => 'Designations',
                'help' => 'Job titles, e.g. Senior Engineer.',
                'fields' => [
                    'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
                    'grade' => ['label' => 'Grade', 'type' => 'text'],
                ],
                'rules' => fn ($id) => [
                    'title' => ['required', 'string', 'max:150', Rule::unique('designations', 'title')->ignore($id)],
                    'grade' => ['nullable', 'string', 'max:30'],
                ],
                'columns' => ['title' => 'Title', 'grade' => 'Grade', 'employees_count' => 'Staff'],
                'with_count' => ['employees'],
                'directory' => null,
                'blocked_by' => fn ($m) => $m->employees()->exists() ? 'Employees still have this designation. Change theirs first.' : null,
            ],
        ];
    }

    public static function get(string $type): array
    {
        $types = self::all();
        abort_unless(isset($types[$type]), 404);

        return ['key' => $type, ...$types[$type]];
    }
}
