<?php

namespace Tests\Feature\Auth;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_both_accounts(): void
    {
        $this->artisan('cms:setup', [
            '--editor-password' => 'editor-pass-12345',
            '--super-password' => 'super-pass-123456',
        ])->assertSuccessful();

        $editor = Account::where('username', 'editor')->firstOrFail();
        $super = Account::where('username', 'superadmin')->firstOrFail();
        $this->assertSame('editor', $editor->role);
        $this->assertSame('super_admin', $super->role);
        $this->assertTrue(Hash::check('editor-pass-12345', $editor->password));
    }

    public function test_rejects_short_passwords(): void
    {
        $this->artisan('cms:setup', [
            '--editor-password' => 'short',
            '--super-password' => 'super-pass-123456',
        ])->assertFailed();

        $this->assertSame(0, Account::count());
    }

    public function test_refuses_to_run_twice_without_force(): void
    {
        $args = ['--editor-password' => 'editor-pass-12345', '--super-password' => 'super-pass-123456'];
        $this->artisan('cms:setup', $args)->assertSuccessful();
        $this->artisan('cms:setup', $args)->assertFailed();
    }
}
