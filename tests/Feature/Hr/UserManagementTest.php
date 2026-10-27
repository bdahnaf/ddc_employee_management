<?php

namespace Tests\Feature\Hr;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_hr_can_open_user_accounts(): void
    {
        $this->actingAs(User::factory()->create())->get('/hr/users')->assertForbidden();
        $this->actingAs(User::factory()->head()->create())->get('/hr/users')->assertForbidden();
        $this->actingAs(User::factory()->hr()->create())->get('/hr/users')->assertOk();
    }

    public function test_employee_cannot_create_accounts(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/hr/users', $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'new.person@ddclbd.com']);
    }

    public function test_hr_can_create_an_account(): void
    {
        $hr = User::factory()->hr()->create();

        $this->actingAs($hr)->post('/hr/users', $this->validPayload())
            ->assertRedirect('/hr/users');

        $user = User::where('email', 'new.person@ddclbd.com')->firstOrFail();
        $this->assertSame(Role::Head, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('Passw0rd99', $user->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.created', 'user_id' => $hr->id]);
    }

    public function test_account_needs_a_valid_unique_email_and_strong_password(): void
    {
        $hr = User::factory()->hr()->create();
        User::factory()->create(['email' => 'taken@ddclbd.com']);

        $this->actingAs($hr)->post('/hr/users', [
            ...$this->validPayload(),
            'email' => 'taken@ddclbd.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_hr_can_change_role_and_reset_password(): void
    {
        $hr = User::factory()->hr()->create();
        $user = User::factory()->create(['password' => 'oldPass123']);

        $this->actingAs($hr)->put("/hr/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'head',
            'password' => 'newPass456',
            'password_confirmation' => 'newPass456',
        ])->assertRedirect('/hr/users');

        $user->refresh();
        $this->assertSame(Role::Head, $user->role);
        $this->assertTrue(Hash::check('newPass456', $user->password));
    }

    public function test_leaving_password_empty_keeps_it(): void
    {
        $hr = User::factory()->hr()->create();
        $user = User::factory()->create(['password' => 'keepMe123']);

        $this->actingAs($hr)->put("/hr/users/{$user->id}", [
            'name' => 'Renamed', 'email' => $user->email, 'role' => 'employee', 'password' => '',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('keepMe123', $user->fresh()->password));
        $this->assertSame('Renamed', $user->fresh()->name);
    }

    public function test_hr_can_deactivate_and_reactivate(): void
    {
        $hr = User::factory()->hr()->create();
        $user = User::factory()->create();

        $this->actingAs($hr)->patch("/hr/users/{$user->id}/toggle")->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($hr)->patch("/hr/users/{$user->id}/toggle")->assertRedirect();
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_hr_cannot_deactivate_themselves(): void
    {
        $hr = User::factory()->hr()->create();

        $this->actingAs($hr)->patch("/hr/users/{$hr->id}/toggle")->assertSessionHasErrors('account');
        $this->assertTrue($hr->fresh()->is_active);
    }

    public function test_last_hr_cannot_be_demoted(): void
    {
        $hr = User::factory()->hr()->create();

        $this->actingAs($hr)->put("/hr/users/{$hr->id}", [
            'name' => $hr->name, 'email' => $hr->email, 'role' => 'employee',
        ])->assertSessionHasErrors('account');

        $this->assertSame(Role::Hr, $hr->fresh()->role);
    }

    public function test_hr_can_be_demoted_when_another_hr_exists(): void
    {
        $hr = User::factory()->hr()->create();
        $other = User::factory()->hr()->create();

        $this->actingAs($hr)->put("/hr/users/{$other->id}", [
            'name' => $other->name, 'email' => $other->email, 'role' => 'employee',
        ])->assertRedirect('/hr/users');

        $this->assertSame(Role::Employee, $other->fresh()->role);
    }

    public function test_search_filters_the_list(): void
    {
        $hr = User::factory()->hr()->create(['name' => 'Hr Person']);
        User::factory()->create(['name' => 'Karim Ahmed']);
        User::factory()->create(['name' => 'Nasrin Akter']);

        $this->actingAs($hr)->get('/hr/users?q=karim')
            ->assertSee('Karim Ahmed')
            ->assertDontSee('Nasrin Akter');
    }

    private function validPayload(): array
    {
        return [
            'name' => 'New Person',
            'email' => 'new.person@ddclbd.com',
            'role' => 'head',
            'password' => 'Passw0rd99',
            'password_confirmation' => 'Passw0rd99',
        ];
    }
}
