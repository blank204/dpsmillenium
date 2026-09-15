<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\AuditEntry;
use App\Models\HtmlPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HtmlPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('pages');
        $a = Account::factory()->superAdmin()->create();
        $this->actingAs($a)->withSession(['session_version' => $a->session_version]);
    }

    private function htmlFile(string $body = '<h1>Seating</h1><script>document.title="x"</script>'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('seating.html', '<!doctype html><html><body>'.$body.'</body></html>');
    }

    public function test_upload_list_serve_sandboxed_and_delete(): void
    {
        $this->post(route('admin.pages.store'), ['actor_name' => 'IT', 'title' => 'Grade VII seating', 'file' => $this->htmlFile()])->assertRedirect(route('admin.pages.index'));
        $page = HtmlPage::firstOrFail();

        $this->get(route('admin.pages.index'))->assertOk()->assertSee('Grade VII seating')->assertSee('#'.$page->id);

        $res = $this->get(route('pages.sandboxed', $page->token));
        $res->assertOk()->assertSee('Seating');
        $csp = $res->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('sandbox', $csp);
        $this->assertStringNotContainsString('allow-same-origin', $csp);
        $this->assertStringNotContainsString('allow-popups-to-escape-sandbox', $csp);
        $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('text/html', $res->headers->get('Content-Type'));

        $this->delete(route('admin.pages.destroy', $page), ['actor_name' => 'IT'])->assertRedirect();
        $this->get(route('pages.sandboxed', $page->token))->assertNotFound();
        Storage::disk('pages')->assertMissing($page->storagePath());
        $this->assertSame(['html_page.uploaded', 'html_page.deleted'], AuditEntry::orderBy('id')->pluck('action')->all());
    }

    public function test_rejects_non_html_and_large_files(): void
    {
        $this->post(route('admin.pages.store'), ['actor_name' => 'IT', 'title' => 'X', 'file' => UploadedFile::fake()->createWithContent('x.html', "%PDF-1.4 binary")])->assertSessionHasErrors('file');
        $this->post(route('admin.pages.store'), ['actor_name' => 'IT', 'title' => 'X', 'file' => UploadedFile::fake()->create('big.html', 3000, 'text/html')])->assertSessionHasErrors('file');
    }

    public function test_editor_cannot_upload(): void
    {
        $e = Account::factory()->editor()->create();
        $this->actingAs($e)->withSession(['session_version' => $e->session_version]);
        $this->post(route('admin.pages.store'), ['actor_name' => 'X', 'title' => 'X', 'file' => $this->htmlFile()])->assertForbidden();
    }
}
