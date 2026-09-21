<?php

namespace Tests\Feature\Admin;

use App\Content\PostStatus;
use App\Content\Section;
use App\Models\Account;
use App\Models\AuditEntry;
use App\Models\HtmlPage;
use App\Models\Post;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The tools added when Claude Design's screens were wired up: CSV export of the change
 * log, filtering it by login, signing out one device, replacing a custom page's file,
 * and running the scheduled job by hand.
 */
class NewAdminToolsTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): Account
    {
        $account = Account::factory()->superAdmin()->create();
        $this->actingAs($account)->withSession(['session_version' => $account->session_version]);

        return $account;
    }

    /* ---------------- change log export ---------------- */

    public function test_change_log_exports_as_csv(): void
    {
        $this->superAdmin();
        AuditEntry::query()->delete();
        $this->post(route('admin.system.run-due'), ['actor_name' => 'Mr. Khan']);

        $response = $this->get(route('admin.audit.export'));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment;', $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'Excel needs the UTF-8 BOM to read accented names');
        $this->assertStringContainsString('Typed name', $csv);
        $this->assertStringContainsString('Mr. Khan', $csv);
        $this->assertStringContainsString('scheduler.run_manually', $csv);
    }

    public function test_exporting_the_change_log_is_itself_recorded(): void
    {
        $this->superAdmin();

        $this->get(route('admin.audit.export'))->assertOk()->streamedContent();

        $this->assertDatabaseHas('audit_entries', ['action' => 'audit.exported']);
    }

    public function test_the_export_honours_the_filters_on_screen(): void
    {
        $this->superAdmin();
        $this->post(route('admin.system.run-due'), ['actor_name' => 'Ms. Priya']);
        $this->post(route('admin.pages.store'), [
            'title' => 'A page',
            'actor_name' => 'Mr. Khan',
            'file' => UploadedFile::fake()->createWithContent('p.html', '<html><body><p>hi</p></body></html>'),
        ]);

        Storage::fake('pages');
        $csv = $this->get(route('admin.audit.export', ['name' => 'Priya']))->assertOk()->streamedContent();

        $this->assertStringContainsString('Ms. Priya', $csv);
        $this->assertStringNotContainsString('Mr. Khan', $csv);
    }

    public function test_the_change_log_can_be_filtered_by_login(): void
    {
        $this->superAdmin();
        $this->post(route('admin.system.run-due'), ['actor_name' => 'Ms. Priya']);

        $this->get(route('admin.audit.index', ['role' => 'super_admin']))->assertOk()->assertSee('Ms. Priya');
        $this->get(route('admin.audit.index', ['role' => 'editor']))->assertOk()->assertDontSee('Ms. Priya');
    }

    public function test_only_super_admin_can_export(): void
    {
        $editor = Account::factory()->editor()->create();
        $this->actingAs($editor)->withSession(['session_version' => $editor->session_version]);

        $this->get(route('admin.audit.export'))->assertForbidden();
    }

    /* ---------------- sign out one device ---------------- */

    private function fakeSession(Account $account, string $id): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $account->id,
            'ip_address' => '10.1.1.1',
            'user_agent' => 'Firefox on Windows',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);
    }

    public function test_one_device_can_be_signed_out_without_touching_the_others(): void
    {
        $account = $this->superAdmin();
        $this->fakeSession($account, 'other-device');
        $this->fakeSession($account, 'a-third-device');

        $this->post(route('admin.security.logout-session'), [
            'session_id' => 'other-device',
            'actor_name' => 'Mr. Khan',
        ])->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
        $this->assertDatabaseHas('sessions', ['id' => 'a-third-device']);
        $this->assertDatabaseHas('audit_entries', ['action' => 'security.session_signed_out', 'actor_name' => 'Mr. Khan']);
    }

    public function test_signing_out_a_device_needs_a_typed_name(): void
    {
        $account = $this->superAdmin();
        $this->fakeSession($account, 'other-device');

        $this->post(route('admin.security.logout-session'), ['session_id' => 'other-device', 'actor_name' => ''])
            ->assertSessionHasErrors('actor_name');

        $this->assertDatabaseHas('sessions', ['id' => 'other-device']);
    }

    public function test_an_already_gone_device_is_not_an_error(): void
    {
        $this->superAdmin();

        $this->post(route('admin.security.logout-session'), ['session_id' => 'never-existed', 'actor_name' => 'Mr. Khan'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_the_security_page_lists_other_devices_with_a_sign_out_button(): void
    {
        $account = $this->superAdmin();
        $this->fakeSession($account, 'other-device');

        $this->get(route('admin.security.edit'))->assertOk()
            ->assertSee('10.1.1.1')
            ->assertSee('Firefox on Windows')
            ->assertSee('Sign out');
    }

    /* ---------------- replace a custom page ---------------- */

    public function test_replacing_a_custom_page_keeps_its_link(): void
    {
        Storage::fake('pages');
        $this->superAdmin();

        $this->post(route('admin.pages.store'), [
            'title' => 'Seating plan',
            'actor_name' => 'Mr. Khan',
            'file' => UploadedFile::fake()->createWithContent('plan.html', '<html><body><h1>Old</h1></body></html>'),
        ])->assertRedirect();

        $page = HtmlPage::firstOrFail();
        $token = $page->token;

        $this->post(route('admin.pages.replace', $page), [
            'actor_name' => 'Mr. Khan',
            'file' => UploadedFile::fake()->createWithContent('plan.html', '<html><body><h1>New seating plan</h1></body></html>'),
        ])->assertRedirect(route('admin.pages.index'));

        $this->assertSame($token, $page->fresh()->token, 'replacing must not change the public link');
        $this->assertStringContainsString('New seating plan', Storage::disk('pages')->get($page->storagePath()));
        $this->assertDatabaseHas('audit_entries', ['action' => 'html_page.replaced']);
    }

    public function test_replacing_rejects_a_file_that_is_not_html(): void
    {
        Storage::fake('pages');
        $this->superAdmin();

        $this->post(route('admin.pages.store'), [
            'title' => 'Seating plan',
            'actor_name' => 'Mr. Khan',
            'file' => UploadedFile::fake()->createWithContent('plan.html', '<html><body><h1>Old</h1></body></html>'),
        ])->assertRedirect();

        $page = HtmlPage::firstOrFail();

        $this->post(route('admin.pages.replace', $page), [
            'actor_name' => 'Mr. Khan',
            'file' => UploadedFile::fake()->createWithContent('sneaky.html', "PK\x03\x04 not really html"),
        ])->assertSessionHasErrors('file');

        $this->assertStringContainsString('Old', Storage::disk('pages')->get($page->storagePath()));
    }

    /* ---------------- run the scheduled job by hand ---------------- */

    public function test_running_the_job_by_hand_publishes_due_posts(): void
    {
        $this->superAdmin();
        $post = Post::factory()->section(Section::Info)->scheduled(CarbonImmutable::now()->subHour())->create();

        $this->post(route('admin.system.run-due'), ['actor_name' => 'Mr. Khan'])->assertRedirect();

        $this->assertSame(PostStatus::Published, $post->fresh()->status);
        $this->assertSame('manual', Setting::get('scheduler.last_source'));
        $this->assertDatabaseHas('audit_entries', ['action' => 'scheduler.run_manually', 'actor_name' => 'Mr. Khan']);
    }

    public function test_running_the_job_by_hand_needs_a_typed_name(): void
    {
        $this->superAdmin();

        $this->post(route('admin.system.run-due'), ['actor_name' => ''])->assertSessionHasErrors('actor_name');
    }

    public function test_running_the_job_twice_does_not_publish_twice(): void
    {
        $this->superAdmin();
        Post::factory()->section(Section::Info)->scheduled(CarbonImmutable::now()->subHour())->create();

        $this->post(route('admin.system.run-due'), ['actor_name' => 'Mr. Khan']);
        $this->post(route('admin.system.run-due'), ['actor_name' => 'Mr. Khan']);

        $this->assertSame(1, AuditEntry::where('action', 'post.auto_published')->count());
    }

    public function test_the_system_page_offers_the_button(): void
    {
        $this->superAdmin();

        $this->get(route('admin.system.show'))->assertOk()
            ->assertSee('Run it now')
            ->assertSee('Backups');
    }

    /* ---------------- help page ---------------- */

    public function test_the_help_page_is_reachable_by_an_editor(): void
    {
        $editor = Account::factory()->editor()->create();
        $this->actingAs($editor)->withSession(['session_version' => $editor->session_version]);

        $this->get(route('admin.tutorial'))->assertOk()
            ->assertSee('How to post a circular')
            ->assertSee('data-screen="tutorial"', false);
    }
}
