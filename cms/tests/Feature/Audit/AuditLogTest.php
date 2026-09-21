<?php

namespace Tests\Feature\Audit;

use App\Audit\AuditChain;
use App\Audit\AuditLogger;
use App\Models\Account;
use App\Models\AuditEntry;
use App\Support\Actor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $name = 'Ms. Priya'): Actor
    {
        return new Actor($name, 1, 'editor', '10.0.0.5', 'Test UA', 'sess-1');
    }

    public function test_record_stores_all_fields_and_chains_hashes(): void
    {
        $logger = app(AuditLogger::class);
        $first = $logger->record('settings.updated', $this->actor(), null, ['a' => 1], ['a' => 2]);
        $second = $logger->record('settings.updated', $this->actor('Mr. Khan'), null, null, ['b' => 'x']);

        $this->assertSame(str_repeat('0', 64), $first->prev_hash);
        $this->assertSame($first->hash, $second->prev_hash);
        $this->assertSame(64, strlen($second->hash));
        $this->assertSame('Ms. Priya', $first->actor_name);
        $this->assertSame('10.0.0.5', $first->ip_address);
        $this->assertSame('sess-1', $first->session_id);
        $this->assertSame(['a' => 1], $first->beforeData());
        $this->assertSame(['a' => 2], $first->afterData());
        $this->assertNull(app(AuditChain::class)->firstBrokenId());
    }

    public function test_entries_cannot_be_updated_through_the_model(): void
    {
        $entry = app(AuditLogger::class)->record('settings.updated', $this->actor());
        $this->expectException(LogicException::class);
        $entry->actor_name = 'Someone else';
        $entry->save();
    }

    public function test_entries_cannot_be_deleted_through_the_model(): void
    {
        $entry = app(AuditLogger::class)->record('settings.updated', $this->actor());
        $this->expectException(LogicException::class);
        $entry->delete();
    }

    public function test_chain_detects_direct_database_tampering(): void
    {
        $logger = app(AuditLogger::class);
        $logger->record('settings.updated', $this->actor());
        $target = $logger->record('settings.updated', $this->actor());
        $logger->record('settings.updated', $this->actor());

        DB::table('audit_entries')->where('id', $target->id)->update(['actor_name' => 'Forged']);

        $this->assertSame($target->id, app(AuditChain::class)->firstBrokenId());
    }

    public function test_verify_command_reports_status(): void
    {
        app(AuditLogger::class)->record('settings.updated', $this->actor());
        $this->artisan('cms:audit-verify')->expectsOutputToContain('intact')->assertSuccessful();
    }

    public function test_login_success_failure_and_logout_are_logged(): void
    {
        Account::factory()->editor()->create(['username' => 'editor']);

        $this->post('/staff-cms/login', ['username' => 'editor', 'password' => 'bad']);
        $this->post('/staff-cms/login', ['username' => 'editor', 'password' => 'correct-horse-battery']);
        $this->post(route('admin.logout'));

        $this->assertSame(
            ['auth.login_failed', 'auth.login', 'auth.logout'],
            AuditEntry::orderBy('id')->pluck('action')->all()
        );
    }
}
