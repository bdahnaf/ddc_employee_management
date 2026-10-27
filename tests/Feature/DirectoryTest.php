<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Posting;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The staff directory and profile pages, with a focus on privacy:
 * regular employees must never see or filter by salary, age, NID, TIN, etc.
 */
class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    private Department $dept;
    private Department $otherDept;
    private Office $hq;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $div = Division::create(['name' => 'Roads']);
        $otherDiv = Division::create(['name' => 'ICT']);
        $this->dept = Department::create(['division_id' => $div->id, 'name' => 'Bridges']);
        $this->otherDept = Department::create(['division_id' => $otherDiv->id, 'name' => 'Software']);
        $this->hq = Office::create(['name' => 'Head Office']);
        $this->project = Project::create(['name' => 'Highway Upgrade', 'division_id' => $div->id, 'status' => 'ongoing']);
    }

    private function employee(string $name, array $attrs = [], string $post = 'hq', int $gross = 50000): Employee
    {
        $e = Employee::create([
            'employee_code' => 'E-'.random_int(1000, 99999),
            'name' => $name,
            'department_id' => $this->dept->id,
            'status' => 'active',
            ...$attrs,
        ]);
        $e->postings()->create([
            'posting_type' => $post,
            'office_id' => $post === 'hq' ? $this->hq->id : null,
            'project_id' => $post === 'project' ? $this->project->id : null,
            'start_date' => '2024-01-01',
        ]);
        $e->salaries()->create(['basic' => $gross, 'gross' => $gross, 'effective_from' => '2024-01-01']);

        return $e;
    }

    public function test_guests_cannot_see_the_directory(): void
    {
        $this->get('/directory')->assertRedirect('/login');
        $this->get('/employees/1')->assertRedirect('/login');
    }

    public function test_everyone_signed_in_can_see_the_directory(): void
    {
        $this->employee('Karim Ahmed');

        $this->actingAs(User::factory()->create())->get('/directory')
            ->assertOk()->assertSee('Karim Ahmed');
    }

    public function test_filter_by_project_and_hq(): void
    {
        $this->employee('Site Person', [], 'project');
        $this->employee('Office Person', [], 'hq');
        $user = User::factory()->create();

        $this->actingAs($user)->get('/directory?place=project:'.$this->project->id)
            ->assertSee('Site Person')->assertDontSee('Office Person');

        $this->actingAs($user)->get('/directory?place=hq')
            ->assertSee('Office Person')->assertDontSee('Site Person');
    }

    public function test_filter_by_department_and_division(): void
    {
        $this->employee('In Bridges');
        $this->employee('In Software', ['department_id' => $this->otherDept->id]);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/directory?place=department:'.$this->otherDept->id)
            ->assertSee('In Software')->assertDontSee('In Bridges');

        $this->actingAs($user)->get('/directory?place=division:'.$this->dept->division_id)
            ->assertSee('In Bridges')->assertDontSee('In Software');
    }

    public function test_transferred_person_only_shows_at_current_place(): void
    {
        $e = $this->employee('Mover', [], 'hq');
        $e->postings()->update(['end_date' => '2025-01-01']);
        $e->postings()->create(['posting_type' => 'project', 'project_id' => $this->project->id, 'start_date' => '2025-01-02']);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/directory?place=hq')->assertDontSee('Mover');
        $this->actingAs($user)->get('/directory?place=projects')->assertSee('Mover');
    }

    public function test_hr_can_filter_by_salary_and_age(): void
    {
        $this->employee('Low Paid', ['date_of_birth' => now()->subYears(25)], 'hq', 30000);
        $this->employee('High Paid', ['date_of_birth' => now()->subYears(50)], 'hq', 120000);
        $hr = User::factory()->hr()->create();

        $this->actingAs($hr)->get('/directory?salary_min=100000')
            ->assertSee('High Paid')->assertDontSee('Low Paid');

        $this->actingAs($hr)->get('/directory?age_max=30')
            ->assertSee('Low Paid')->assertDontSee('High Paid');
    }

    public function test_salary_and_age_filters_are_ignored_for_regular_employees(): void
    {
        $this->employee('Low Paid', ['date_of_birth' => now()->subYears(25)], 'hq', 30000);
        $this->employee('High Paid', ['date_of_birth' => now()->subYears(50)], 'hq', 120000);
        $user = User::factory()->create();

        // If the filter worked, it would reveal who earns over 100k. It must not.
        $this->actingAs($user)->get('/directory?salary_min=100000')
            ->assertSee('High Paid')->assertSee('Low Paid')
            ->assertDontSee('Age &amp; salary', false);

        $this->actingAs($user)->get('/directory?age_max=30')
            ->assertSee('High Paid')->assertSee('Low Paid');
    }

    public function test_sorting_by_salary_is_ignored_for_regular_employees(): void
    {
        $this->employee('Zed Highest', [], 'hq', 200000);
        $this->employee('Amy Lowest', [], 'hq', 10000);

        $html = $this->actingAs(User::factory()->create())->get('/directory?sort=salary')->getContent();
        $this->assertLessThan(strpos($html, 'Zed Highest'), strpos($html, 'Amy Lowest'), 'Should stay alphabetical, not salary order');

        $html = $this->actingAs(User::factory()->hr()->create())->get('/directory?sort=salary')->getContent();
        $this->assertLessThan(strpos($html, 'Amy Lowest'), strpos($html, 'Zed Highest'), 'HR sees highest salary first');
    }

    public function test_regular_employee_cannot_see_someone_elses_private_details(): void
    {
        $e = $this->employee('Private Person', ['nid_no' => '1990123456789', 'tin_no' => 'TIN-55511', 'bank_account_no' => 'ACC-998877', 'personal_phone' => '01700-000111'], 'hq', 87654);

        $this->actingAs(User::factory()->create())->get("/employees/{$e->id}")
            ->assertOk()
            ->assertSee('Private Person')
            ->assertDontSee('1990123456789')
            ->assertDontSee('TIN-55511')
            ->assertDontSee('ACC-998877')
            ->assertDontSee('01700-000111')
            ->assertDontSee('87,654')
            ->assertSee('visible only to HR');
    }

    public function test_hr_sees_private_details(): void
    {
        $e = $this->employee('Private Person', ['nid_no' => '1990123456789'], 'hq', 87654);

        $this->actingAs(User::factory()->hr()->create())->get("/employees/{$e->id}")
            ->assertSee('1990123456789')->assertSee('87,654');
    }

    public function test_employee_sees_their_own_private_details(): void
    {
        $user = User::factory()->create();
        $e = $this->employee('Me Myself', ['nid_no' => '1990123456789', 'user_id' => $user->id], 'hq', 87654);

        $this->actingAs($user)->get('/me')->assertRedirect("/employees/{$e->id}");
        $this->actingAs($user)->get("/employees/{$e->id}")
            ->assertSee('1990123456789')->assertSee('87,654')->assertSee('This is your profile');
    }

    public function test_head_does_not_see_private_details_of_others(): void
    {
        $e = $this->employee('Team Member', ['nid_no' => '1990123456789'], 'hq', 87654);

        $this->actingAs(User::factory()->head()->create())->get("/employees/{$e->id}")
            ->assertDontSee('1990123456789')->assertDontSee('87,654');
    }

    public function test_me_without_linked_record_explains_what_to_do(): void
    {
        $this->actingAs(User::factory()->create())->get('/me')
            ->assertOk()->assertSee('linked to a staff record yet');
    }
}
