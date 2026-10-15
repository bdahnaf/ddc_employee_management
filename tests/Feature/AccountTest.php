<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_is_for_hr_and_others_go_to_directory(): void
    {
        $this->actingAs(User::factory()->hr()->create())->get('/dashboard')
            ->assertOk()->assertSee('Recent activity');

        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertRedirect('/directory');
    }

    public function test_homepage_links_into_the_app(): void
    {
        $this->get('/')->assertOk()->assertSee(route('directory'))->assertSee('Sign in');
        $this->actingAs(User::factory()->create())->get('/')->assertSee('Open Workforce');
    }

    public function test_user_can_change_own_password(): void
    {
        $user = User::factory()->create(['password' => 'oldPass123']);

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'oldPass123',
            'password' => 'newPass456',
            'password_confirmation' => 'newPass456',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('newPass456', $user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'oldPass123']);

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'nope',
            'password' => 'newPass456',
            'password_confirmation' => 'newPass456',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('oldPass123', $user->fresh()->password));
    }

    public function test_hr_create_command_makes_first_hr_account(): void
    {
        $this->artisan('hr:create', ['email' => 'hr@ddclbd.com', '--name' => 'HR Admin'])
            ->expectsQuestion('Password (at least 8 characters, letters and numbers)', 'Start1234')
            ->expectsQuestion('Confirm password', 'Start1234')
            ->assertSuccessful();

        $user = User::where('email', 'hr@ddclbd.com')->firstOrFail();
        $this->assertTrue($user->isHr());
    }
}
