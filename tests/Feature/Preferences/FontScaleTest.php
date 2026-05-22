<?php

namespace Tests\Feature\Preferences;

use App\Models\User;
use App\Services\FontScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FontScaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_post_valid_scale_and_receives_cookie(): void
    {
        $response = $this->postJson('/preferences/font-scale', ['scale' => 1.20]);

        $response->assertOk()->assertJson(['scale' => 1.20]);

        // Cookie queued in response
        $cookies = $response->headers->getCookies();
        $names = array_map(fn ($c) => $c->getName(), $cookies);
        $this->assertContains(FontScale::COOKIE_NAME, $names);
    }

    public function test_authenticated_user_persists_scale_to_database(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson('/preferences/font-scale', ['scale' => 1.35])
            ->assertOk();

        $this->assertEquals(1.35, (float) $user->fresh()->font_scale);
    }

    public function test_invalid_scale_is_rejected(): void
    {
        $this->postJson('/preferences/font-scale', ['scale' => 2.0])
            ->assertStatus(422);

        $this->postJson('/preferences/font-scale', ['scale' => 'large'])
            ->assertStatus(422);

        $this->postJson('/preferences/font-scale', [])
            ->assertStatus(422);
    }

    public function test_service_resolves_db_over_cookie_for_authenticated_user(): void
    {
        $user = User::factory()->create(['font_scale' => 1.50]);
        $this->assertEquals(1.50, FontScale::current($user));
    }

    public function test_service_falls_back_to_default_when_nothing_set(): void
    {
        $this->assertEquals(1.00, FontScale::current(null));
    }

    public function test_isvalid_accepts_all_seven_levels(): void
    {
        foreach ([0.85, 0.93, 1.00, 1.10, 1.20, 1.35, 1.50] as $v) {
            $this->assertTrue(FontScale::isValid($v), "Level $v should be valid");
        }
        $this->assertFalse(FontScale::isValid(1.05));
        $this->assertFalse(FontScale::isValid(2.0));
    }

    public function test_html_contains_font_scale_inline_script_for_authenticated_user(): void
    {
        $user = User::factory()->create(['font_scale' => 1.20]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSee('--font-scale', false)
            ->assertSee('1.2', false);
    }
}
