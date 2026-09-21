<?php

namespace Tests\Feature\Content;

use App\Models\CalendarType;
use App\Models\Grade;
use App\Models\NewsletterSeries;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_exist_after_migration(): void
    {
        $this->assertSame(
            ['Pre-KG', 'KG1', 'KG2', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'],
            Grade::ordered()->pluck('label')->all()
        );
        $this->assertSame(['Holiday', 'Exam'], CalendarType::orderBy('id')->pluck('name')->all());
        $this->assertSame(0, NewsletterSeries::count());
        $this->assertSame(5, Setting::get('homepage_count.info'));
        $this->assertSame(4, Setting::get('homepage_count.circular'));
        $this->assertSame(8, Setting::get('homepage_count.event'));
        $this->assertSame(4, Setting::get('homepage_count.newsletter'));
        $this->assertSame(4, Setting::get('academic_year_start_month'));
    }

    public function test_put_and_get(): void
    {
        Setting::put('homepage_count.info', 7);
        $this->assertSame(7, Setting::get('homepage_count.info'));
        $this->assertSame('fallback', Setting::get('missing.key', 'fallback'));
    }
}
