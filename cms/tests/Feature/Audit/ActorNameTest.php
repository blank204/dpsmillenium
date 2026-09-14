<?php

namespace Tests\Feature\Audit;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActorNameTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        $a = Account::factory()->editor()->create();
        $this->actingAs($a)->withSession(['session_version' => $a->session_version]);
    }

    public function test_blank_name_is_rejected(): void
    {
        $this->login();
        $this->post('/staff-cms/_actor-probe', ['actor_name' => '   '])->assertSessionHasErrors('actor_name');
    }

    public function test_missing_name_is_rejected(): void
    {
        $this->login();
        $this->post('/staff-cms/_actor-probe', [])->assertSessionHasErrors('actor_name');
    }

    public function test_too_long_name_is_rejected(): void
    {
        $this->login();
        $this->post('/staff-cms/_actor-probe', ['actor_name' => str_repeat('a', 81)])->assertSessionHasErrors('actor_name');
    }

    public function test_valid_name_is_trimmed_and_available_as_actor(): void
    {
        $this->login();
        $this->post('/staff-cms/_actor-probe', ['actor_name' => '  Ms. Priya  '])->assertOk()->assertSee('Ms. Priya|editor');
    }
}
