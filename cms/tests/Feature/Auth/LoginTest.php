<?php

namespace Tests\Feature\Auth;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_under_admin_path(): void
    {
        $this->get('/staff-cms/login')->assertOk()->assertSee('name="username"', false);
        $this->get('/admin/login')->assertNotFound();
    }

    public function test_editor_can_log_in_and_reach_dashboard(): void
    {
        Account::factory()->editor()->create(['username' => 'editor']);

        $this->post('/staff-cms/login', ['username' => 'editor', 'password' => 'correct-horse-battery'])
            ->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_wrong_password_is_rejected(): void
    {
        Account::factory()->editor()->create(['username' => 'editor']);

        $this->post('/staff-cms/login', ['username' => 'editor', 'password' => 'nope'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_lockout_after_five_failures(): void
    {
        Account::factory()->editor()->create(['username' => 'editor']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/staff-cms/login', ['username' => 'editor', 'password' => 'nope']);
        }

        $this->post('/staff-cms/login', ['username' => 'editor', 'password' => 'correct-horse-battery'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_editor_cannot_reach_super_admin_route(): void
    {
        $editor = Account::factory()->editor()->create();
        $this->actingAs($editor)->withSession(['session_version' => $editor->session_version])
            ->get('/staff-cms/_role-probe')->assertForbidden();

        $super = Account::factory()->superAdmin()->create();
        $this->actingAs($super)->withSession(['session_version' => $super->session_version])
            ->get('/staff-cms/_role-probe')->assertOk();
    }

    public function test_bumping_session_version_logs_out_existing_sessions(): void
    {
        $editor = Account::factory()->editor()->create(['username' => 'editor']);
        $this->post('/staff-cms/login', ['username' => 'editor', 'password' => 'correct-horse-battery']);
        $this->get(route('admin.dashboard'))->assertOk();

        $editor->bumpSessionVersion();

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_logout(): void
    {
        Account::factory()->editor()->create(['username' => 'editor']);
        $this->post('/staff-cms/login', ['username' => 'editor', 'password' => 'correct-horse-battery']);
        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }
}
