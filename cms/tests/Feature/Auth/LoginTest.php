<?php

namespace Tests\Feature\Auth;

use App\Models\Account;
use App\Models\AuditEntry;
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

    /**
     * The DB collation (utf8mb4_unicode_ci) treats accented, upper-case and
     * full-width spellings as the same username, so they must share one lockout.
     */
    public function test_accent_case_and_full_width_variants_share_the_lockout(): void
    {
        Account::factory()->editor()->create(['username' => 'editor']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/staff-cms/login', ['username' => 'editor', 'password' => 'nope']);
        }

        foreach (['ÉDITOR', 'édïtör', 'ｅｄｉｔｏｒ'] as $variant) {
            $this->post('/staff-cms/login', ['username' => $variant, 'password' => 'correct-horse-battery'])
                ->assertSessionHasErrors('username');
            $this->assertGuest();
        }
    }

    public function test_variant_spellings_do_not_get_a_fresh_attempt_budget(): void
    {
        Account::factory()->editor()->create(['username' => 'editor']);

        foreach (['editor', 'Editor', 'édïtör', 'ÉDITOR', 'ｅｄｉｔｏｒ'] as $variant) {
            $this->post('/staff-cms/login', ['username' => $variant, 'password' => 'nope']);
        }

        $this->post('/staff-cms/login', ['username' => 'editor', 'password' => 'correct-horse-battery'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_lockout_is_audited_once_and_blocked_attempts_do_not_add_rows(): void
    {
        $editor = Account::factory()->editor()->create(['username' => 'editor']);

        for ($i = 0; $i < 30; $i++) {
            $this->post('/staff-cms/login', ['username' => $i % 2 ? 'editor' : 'édïtör', 'password' => 'nope']);
        }

        $this->assertSame(5, AuditEntry::where('action', 'auth.login_failed')->count());
        $lockouts = AuditEntry::where('action', 'auth.lockout')->get();
        $this->assertCount(1, $lockouts);
        $this->assertSame($editor->id, (int) $lockouts[0]->target_id);
        $this->assertNotNull($lockouts[0]->occurred_at);
        $this->assertNotNull($lockouts[0]->ip_address);
        $this->assertStringContainsString('attempted_username', (string) $lockouts[0]->after_json);
    }

    public function test_unknown_usernames_share_one_per_ip_budget_and_audit_is_bounded(): void
    {
        Account::factory()->editor()->create(['username' => 'editor']);

        for ($i = 0; $i < 40; $i++) {
            $this->post('/staff-cms/login', ['username' => 'nobody'.$i, 'password' => 'nope'])
                ->assertSessionHasErrors('username');
        }

        $this->assertSame(5, AuditEntry::where('action', 'auth.login_failed')->count());
        $this->assertSame(1, AuditEntry::where('action', 'auth.lockout')->count());

        // A real account from the same IP keeps its own budget.
        $this->post('/staff-cms/login', ['username' => 'editor', 'password' => 'correct-horse-battery'])
            ->assertRedirect(route('admin.dashboard'));
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
