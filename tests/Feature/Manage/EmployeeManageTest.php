<?php

namespace Tests\Feature\Manage;

use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeManageTest extends TestCase
{
    use RefreshDatabase;

    private Department $dept;
    private Office $hq;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $div = Division::create(['name' => 'Roads']);
        $this->dept = Department::create(['division_id' => $div->id, 'name' => 'Bridges']);
        $this->hq = Office::create(['name' => 'Head Office']);
        $this->project = Project::create(['name' => 'Highway Upgrade', 'status' => 'ongoing']);
    }

    private function payload(array $extra = []): array
    {
        return [
            'name' => 'New Engineer',
            'employee_code' => 'DDC-0500',
            'department_id' => $this->dept->id,
            'status' => 'active',
            'employment_type' => 'permanent',
            'joining_date' => '2024-03-01',
            'posting_type' => 'hq',
            'office_id' => $this->hq->id,
            'posting_start' => '2024-03-01',
            'basic' => 40000,
            'house_rent' => 20000,
            ...$extra,
        ];
    }

    public function test_only_hr_can_add_employees(): void
    {
        $this->actingAs(User::factory()->create())->get('/manage/employees/create')->assertForbidden();
        $this->actingAs(User::factory()->create())->post('/manage/employees', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_hr_adds_employee_with_posting_salary_and_photo(): void
    {
        Storage::fake('local');
        $hr = User::factory()->hr()->create();

        $this->actingAs($hr)->post('/manage/employees', $this->payload([
            'photo' => UploadedFile::fake()->image('face.jpg', 400, 400),
        ]))->assertRedirect();

        $e = Employee::firstOrFail();
        $this->assertSame('New Engineer', $e->name);
        $this->assertNotNull($e->photo_path);
        Storage::disk('local')->assertExists($e->photo_path);
        $this->assertSame('hq', $e->currentPosting->posting_type);
        $this->assertEquals(60000, (float) $e->currentSalary->gross);
        $this->assertDatabaseHas('activity_logs', ['action' => 'employee.created']);
    }

    public function test_photo_is_only_served_to_signed_in_users(): void
    {
        Storage::fake('local');
        $hr = User::factory()->hr()->create();
        $this->actingAs($hr)->post('/manage/employees', $this->payload(['photo' => UploadedFile::fake()->image('face.jpg')]));
        $e = Employee::firstOrFail();

        $this->actingAs(User::factory()->create())->get("/employees/{$e->id}/photo")->assertOk();
        auth()->logout();
        $this->get("/employees/{$e->id}/photo")->assertRedirect('/login');
    }

    public function test_project_posting_needs_a_project(): void
    {
        $this->actingAs(User::factory()->hr()->create())
            ->post('/manage/employees', $this->payload(['posting_type' => 'project', 'office_id' => null]))
            ->assertSessionHasErrors('project_id');
    }

    public function test_transfer_closes_old_posting_and_keeps_history(): void
    {
        $hr = User::factory()->hr()->create();
        $this->actingAs($hr)->post('/manage/employees', $this->payload());
        $e = Employee::firstOrFail();

        $this->actingAs($hr)->post("/manage/employees/{$e->id}/transfer", [
            'posting_type' => 'project',
            'project_id' => $this->project->id,
            'start_date' => '2025-06-01',
            'order_ref' => 'OO-12/2025',
        ])->assertRedirect("/employees/{$e->id}");

        $e->refresh();
        $this->assertCount(2, $e->postings);
        $this->assertSame('project', $e->currentPosting->posting_type);
        $old = $e->postings->firstWhere('posting_type', 'hq');
        $this->assertSame('2025-05-31', $old->end_date->toDateString());
        $this->assertDatabaseHas('activity_logs', ['action' => 'posting.created']);
    }

    public function test_transfer_cannot_start_before_current_posting(): void
    {
        $hr = User::factory()->hr()->create();
        $this->actingAs($hr)->post('/manage/employees', $this->payload());
        $e = Employee::firstOrFail();

        $this->actingAs($hr)->post("/manage/employees/{$e->id}/transfer", [
            'posting_type' => 'project', 'project_id' => $this->project->id, 'start_date' => '2023-01-01',
        ])->assertSessionHasErrors('start_date');

        $this->assertCount(1, $e->postings()->get());
    }

    public function test_salary_change_keeps_old_salary_in_history(): void
    {
        $hr = User::factory()->hr()->create();
        $this->actingAs($hr)->post('/manage/employees', $this->payload());
        $e = Employee::firstOrFail();

        $this->actingAs($hr)->put("/manage/employees/{$e->id}", [
            ...$this->payload(),
            'basic' => 50000,
            'house_rent' => 25000,
            'salary_from' => '2025-07-01',
        ])->assertRedirect();

        $e->refresh();
        $this->assertCount(2, $e->salaries);
        $this->assertEquals(75000, (float) $e->currentSalary->gross);
        $this->assertSame('2025-06-30', $e->salaries->last()->effective_to->toDateString());
    }

    public function test_employee_cannot_transfer_or_edit(): void
    {
        $hr = User::factory()->hr()->create();
        $this->actingAs($hr)->post('/manage/employees', $this->payload());
        $e = Employee::firstOrFail();
        $user = User::factory()->create();

        $this->actingAs($user)->get("/manage/employees/{$e->id}/edit")->assertForbidden();
        $this->actingAs($user)->post("/manage/employees/{$e->id}/transfer", ['posting_type' => 'hq', 'office_id' => $this->hq->id, 'start_date' => '2026-01-01'])->assertForbidden();
        $this->actingAs($user)->delete("/manage/employees/{$e->id}")->assertForbidden();
    }

    public function test_removed_employee_disappears_from_directory(): void
    {
        $hr = User::factory()->hr()->create();
        $this->actingAs($hr)->post('/manage/employees', $this->payload());
        $e = Employee::firstOrFail();

        $this->actingAs($hr)->delete("/manage/employees/{$e->id}")->assertRedirect('/directory');
        $this->assertSoftDeleted($e);
        $this->actingAs($hr)->get('/directory')->assertSee('has been removed')->assertDontSee('DDC-0500');
    }
}
