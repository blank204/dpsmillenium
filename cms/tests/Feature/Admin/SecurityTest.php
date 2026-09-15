<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\AuditEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private Account $super;
    private Account $editor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->editor = Account::factory()->editor()->create(['username' => 'editor']);
        $this->super = Account::factory()->superAdmin()->create(['username' => 'superadmin']);
        $this->actingAs($this->super)->withSession(['session_version' => $this->super->session_version]);
    }

    public function test_page_renders(): void
    {
        $this->get(route('admin.security.edit'))->assertOk()->assertSee('Change the shared Editor password');
    }

    public function test_change_editor_password_logs_out_editors(): void
    {
        $before = $this->editor->session_version;

        $this->post(route('admin.security.editor-password'), ['actor_name' => 'IT', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->post(route('admin.security.editor-password'), ['actor_name' => 'IT', 'password' => 'new-editor-pass-1', 'password_confirmation' => 'new-editor-pass-1'])->assertRedirect();

        $this->editor->refresh();
        $this->assertTrue(Hash::check('new-editor-pass-1', $this->editor->password));
        $this->assertSame($before + 1, $this->editor->session_version);
        $entry = AuditEntry::where('action', 'security.editor_password_changed')->firstOrFail();
        $this->assertStringNotContainsString('new-editor-pass-1', (string) $entry->after_json);
    }

    public function test_change_own_password_requires_current_and_keeps_session(): void
    {
        $this->post(route('admin.security.own-password'), ['actor_name' => 'IT', 'current_password' => 'wrong', 'password' => 'new-super-pass-12', 'password_confirmation' => 'new-super-pass-12'])
            ->assertSessionHasErrors('current_password');

        $this->post(route('admin.security.own-password'), ['actor_name' => 'IT', 'current_password' => 'correct-horse-battery', 'password' => 'new-super-pass-12', 'password_confirmation' => 'new-super-pass-12'])
            ->assertRedirect();

        $this->get(route('admin.security.edit'))->assertOk();
        $this->assertTrue(AuditEntry::where('action', 'security.own_password_changed')->exists());
    }

    public function test_logout_all_bumps_every_account(): void
    {
        $this->post(route('admin.security.logout-all'), ['actor_name' => 'IT'])->assertRedirect(route('admin.login'));
        $this->assertSame(2, $this->editor->fresh()->session_version);
        $this->assertSame(2, $this->super->fresh()->session_version);
        $this->assertTrue(AuditEntry::where('action', 'security.force_logout')->exists());
    }
}
