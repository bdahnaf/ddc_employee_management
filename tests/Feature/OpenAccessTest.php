<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Open access (config/workforce.php, WORKFORCE_OPEN_ACCESS=true): no sign-in,
 * every page and private detail visible. The other tests run with it switched off.
 */
class OpenAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Routes read the switch while the app boots, so set it before parent::setUp().
        putenv('WORKFORCE_OPEN_ACCESS=true');
        $_ENV['WORKFORCE_OPEN_ACCESS'] = $_SERVER['WORKFORCE_OPEN_ACCESS'] = 'true';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('WORKFORCE_OPEN_ACCESS=false');
        $_ENV['WORKFORCE_OPEN_ACCESS'] = $_SERVER['WORKFORCE_OPEN_ACCESS'] = 'false';
    }

    public function test_every_page_opens_without_signing_in(): void
    {
        $this->seed();
        $e = Employee::firstOrFail();

        foreach (['/', '/directory', '/directory?place=projects', '/directory?place=hq', "/employees/{$e->id}",
            '/dashboard', '/manage/employees/create', "/manage/employees/{$e->id}/edit",
            '/manage/divisions', '/manage/departments', '/manage/offices', '/manage/projects', '/manage/designations', '/hr/users'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_private_details_and_hr_filters_are_visible(): void
    {
        $this->seed();
        $e = Employee::whereNotNull('nid_no')->firstOrFail();

        $this->get("/employees/{$e->id}")->assertSee($e->nid_no)->assertSee('Gross per month')->assertDontSee('visible only to HR');
        $this->get('/directory')->assertSee('Age &amp; salary', false)->assertSee('Open access');
    }

    public function test_changes_can_be_made_without_signing_in(): void
    {
        $this->seed();
        $dept = \App\Models\Department::firstOrFail();
        $office = \App\Models\Office::firstOrFail();

        $this->post('/manage/employees', [
            'name' => 'Walk-in Test', 'employee_code' => 'T-1', 'department_id' => $dept->id,
            'status' => 'active', 'employment_type' => 'permanent',
            'posting_type' => 'hq', 'office_id' => $office->id, 'posting_start' => '2026-01-01',
        ])->assertRedirect();

        $this->assertDatabaseHas('employees', ['name' => 'Walk-in Test']);
    }

    public function test_dummy_data_seeds_and_removes_cleanly(): void
    {
        $this->seed();
        $this->assertSame(60, Employee::count());
        $this->assertSame(0, Employee::whereHas('postings', fn ($p) => $p->whereNull('end_date'), '>', 1)->count(), 'Nobody has two current postings');

        $this->artisan('demo:data --remove')->assertSuccessful();
        $this->assertSame(0, Employee::withTrashed()->count());
        $this->assertSame(0, \App\Models\Division::withTrashed()->count());
    }
}
