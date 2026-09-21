<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_versions_and_scheduler(): void
    {
        $a = Account::factory()->superAdmin()->create();
        $this->actingAs($a)->withSession(['session_version' => $a->session_version]);
        Setting::put('scheduler.last_source', 'visitor');
        Setting::put('scheduler.last_run_at', now()->toIso8601String());

        $this->get(route('admin.system.show'))->assertOk()
            ->assertSee(PHP_VERSION)
            ->assertSee('visitor')
            ->assertSee('Backups')
            ->assertSee('Not set up yet');
    }
}
