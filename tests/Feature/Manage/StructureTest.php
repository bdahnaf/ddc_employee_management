<?php

namespace Tests\Feature\Manage;

use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Divisions, departments, offices, projects and designations (HR only). */
class StructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_type_page_loads_for_hr_and_is_forbidden_for_others(): void
    {
        $hr = User::factory()->hr()->create();
        $user = User::factory()->create();

        foreach (['divisions', 'departments', 'offices', 'projects', 'designations'] as $type) {
            $this->actingAs($hr)->get("/manage/{$type}")->assertOk();
            $this->actingAs($hr)->get("/manage/{$type}/create")->assertOk();
            $this->actingAs($user)->get("/manage/{$type}")->assertForbidden();
        }
    }

    public function test_hr_can_create_edit_and_remove_a_division(): void
    {
        $hr = User::factory()->hr()->create();

        $this->actingAs($hr)->post('/manage/divisions', ['name' => 'Roads & Bridges', 'code' => 'RB', 'is_active' => '1'])
            ->assertRedirect('/manage/divisions');
        $div = Division::firstOrFail();

        $this->actingAs($hr)->put("/manage/divisions/{$div->id}", ['name' => 'Roads and Bridges', 'code' => 'RB'])
            ->assertRedirect('/manage/divisions');
        $this->assertSame('Roads and Bridges', $div->fresh()->name);
        $this->assertFalse($div->fresh()->is_active, 'Unticked checkbox means inactive');

        $this->actingAs($hr)->delete("/manage/divisions/{$div->id}")->assertRedirect();
        $this->assertSoftDeleted($div);
    }

    public function test_division_names_must_be_unique(): void
    {
        $hr = User::factory()->hr()->create();
        Division::create(['name' => 'ICT']);

        $this->actingAs($hr)->post('/manage/divisions', ['name' => 'ICT'])->assertSessionHasErrors('name');
    }

    public function test_department_needs_a_division(): void
    {
        $this->actingAs(User::factory()->hr()->create())
            ->post('/manage/departments', ['name' => 'Software'])
            ->assertSessionHasErrors('division_id');
    }

    public function test_cannot_remove_division_that_has_departments(): void
    {
        $hr = User::factory()->hr()->create();
        $div = Division::create(['name' => 'ICT']);
        Department::create(['division_id' => $div->id, 'name' => 'Software']);

        $this->actingAs($hr)->from('/manage/divisions')->delete("/manage/divisions/{$div->id}")
            ->assertSessionHasErrors('account');
        $this->assertNotSoftDeleted($div);
    }

    public function test_cannot_remove_department_with_employees(): void
    {
        $hr = User::factory()->hr()->create();
        $div = Division::create(['name' => 'ICT']);
        $dept = Department::create(['division_id' => $div->id, 'name' => 'Software']);
        Employee::create(['employee_code' => 'X1', 'name' => 'Someone', 'department_id' => $dept->id]);

        $this->actingAs($hr)->from('/manage/departments')->delete("/manage/departments/{$dept->id}")
            ->assertSessionHasErrors('account');
    }

    public function test_project_end_date_cannot_be_before_start(): void
    {
        $this->actingAs(User::factory()->hr()->create())->post('/manage/projects', [
            'name' => 'Bridge', 'status' => 'ongoing', 'start_date' => '2025-05-01', 'end_date' => '2025-01-01',
        ])->assertSessionHasErrors('end_date');

        $this->assertSame(0, Project::count());
    }
}
