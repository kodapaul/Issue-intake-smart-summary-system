<?php

namespace Tests\Feature;

use Database\Seeders\PlaybookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaybookApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlaybookSeeder::class);
    }

    public function test_index_returns_all_seeded_playbook_entries(): void
    {
        $response = $this->getJson('/api/playbook');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_omits_triggers_by_default(): void
    {
        $response = $this->getJson('/api/playbook');

        $response->assertOk();
        $this->assertArrayNotHasKey('triggers', $response->json('data.0'));
    }

    public function test_index_includes_triggers_when_query_param_set(): void
    {
        $response = $this->getJson('/api/playbook?include_triggers=true');

        $response->assertOk();
        $this->assertIsArray($response->json('data.0.triggers'));
    }

    public function test_show_returns_full_entry_with_steps_and_faqs(): void
    {
        $response = $this->getJson('/api/playbook/promo_codes');

        $response->assertOk();
        $response->assertJsonPath('data.slug', 'promo_codes');
        $response->assertJsonPath('data.name', 'Promo Codes & Discounts');
        $this->assertNotEmpty($response->json('data.troubleshooting_steps'));
        $this->assertNotEmpty($response->json('data.faqs'));
        $this->assertArrayHasKey('q', $response->json('data.faqs.0'));
        $this->assertArrayHasKey('a', $response->json('data.faqs.0'));
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/playbook/nonexistent')->assertNotFound();
    }
}
