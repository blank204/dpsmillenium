<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\CalendarEntry;
use App\Models\CalendarType;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActorNameRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_actor_name_input_is_blank_and_not_autocompleted(): void
    {
        $editor = Account::factory()->editor()->create();
        $this->actingAs($editor)->withSession(['session_version' => $editor->session_version, '_old_input' => ['actor_name' => 'Remembered']]);

        $post = Post::factory()->published()->create(['pinned_until' => now()->addDay()]);
        CalendarEntry::create(['title' => 'Exam', 'calendar_type_id' => CalendarType::value('id'), 'start_date' => now()->format('Y-m-d')]);

        foreach ([
            route('admin.posts.create', 'info'),
            route('admin.posts.edit', $post),
            route('admin.dashboard'),
            route('admin.calendar.index'),
            route('admin.posts.index'),
        ] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match_all('/<input[^>]*name="actor_name"[^>]*>/', $html, $m);
            $this->assertNotEmpty($m[0], "no actor_name input on {$url}");
            foreach ($m[0] as $input) {
                $this->assertStringNotContainsString('value=', $input, $url);
                $this->assertStringContainsString('autocomplete="off"', $input, $url);
                $this->assertStringContainsString('required', $input, $url);
            }
            $this->assertStringNotContainsString('Remembered', $html);
        }
    }
}
