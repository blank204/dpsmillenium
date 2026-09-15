<?php

namespace Tests\Feature;

use App\Content\Section;
use App\Models\Account;
use App\Models\AuditEntry;
use App\Models\CalendarEntry;
use App\Models\Post;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_every_section_and_state(): void
    {
        Storage::fake('media');
        $this->seed(DemoSeeder::class);

        $this->assertSame(2, Account::count());
        $this->assertSame(8, Post::withTrashed()->ofSection(Section::Info)->count());
        $this->assertSame(6, Post::ofSection(Section::Circular)->count());
        $this->assertSame(4, Post::ofSection(Section::Event)->count());
        $this->assertSame(3, Post::ofSection(Section::Newsletter)->count());
        $this->assertSame(6, CalendarEntry::count());

        $states = Post::withTrashed()->get()->map->adminStatus()->unique()->sort()->values()->all();
        $this->assertSame(['deleted', 'draft', 'hidden', 'live', 'scheduled'], $states);
        $this->assertTrue(Post::whereNotNull('pinned_until')->exists());
        $this->assertGreaterThan(20, AuditEntry::count());
        $this->assertNull(app(\App\Audit\AuditChain::class)->firstBrokenId());
    }

    public function test_refuses_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->expectException(\RuntimeException::class);
        (new DemoSeeder())->setContainer($this->app)->__invoke();
    }
}
