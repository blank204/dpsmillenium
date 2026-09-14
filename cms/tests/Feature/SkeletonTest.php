<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SkeletonTest extends TestCase
{
    use RefreshDatabase;

    public function test_timezone_is_qatar(): void
    {
        $this->assertSame('Asia/Qatar', config('app.timezone'));
        $this->assertSame('+03:00', now()->format('P'));
    }

    public function test_database_is_mysql_family(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('cms_test', DB::connection()->getDatabaseName());
    }

    public function test_admin_path_is_not_admin(): void
    {
        $this->assertNotSame('admin', config('cms.admin_path'));
        $this->assertSame('staff-cms', config('cms.admin_path'));
    }
}
