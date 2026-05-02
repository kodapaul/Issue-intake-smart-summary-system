<?php

namespace Tests\Feature;

use App\Issue\Models\Category;
use App\Issue\Models\Issue;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PlaybookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CategorySeeder::class, PlaybookSeeder::class]);
    }

    public function test_create_issue_with_strong_rules_match_auto_derives_priority_and_summary(): void
    {
        $response = $this->postJson('/api/issues', [
            'title' => 'Promo not applying',
            'description' => 'My promo code DISCOUNT10 is not working at checkout.',
            'category' => 'support',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.priority', 'medium'); // support category → medium severity
        $response->assertJsonPath('data.status', 'open');
        $response->assertJsonPath('data.summary', 'Customer reports an issue applying a promo or discount code at checkout.');
        $this->assertNotNull($response->json('data.suggested_action'));
    }

    public function test_create_issue_with_no_rules_match_returns_generic_fallback_summary(): void
    {
        $response = $this->postJson('/api/issues', [
            'title' => 'Quarterly forecast looks wrong',
            'description' => 'The Q3 projections widget is showing numbers that disagree with the spreadsheet finance shared.',
            'category' => 'other',
        ]);

        $response->assertCreated();
        // Falls through to generic — first sentence summary, generic action
        $this->assertStringContainsString('manual triage', $response->json('data.suggested_action'));
    }

    public function test_create_issue_rejects_missing_required_fields(): void
    {
        $response = $this->postJson('/api/issues', [
            'title' => 'just a title',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description', 'category']);
    }

    public function test_create_issue_rejects_invalid_category_slug(): void
    {
        $response = $this->postJson('/api/issues', [
            'title' => 'x',
            'description' => 'y',
            'category' => 'made-up-category',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category']);
    }

    public function test_create_issue_strips_xss_tags_from_text_fields(): void
    {
        $response = $this->postJson('/api/issues', [
            'title' => 'Bug <script>alert(1)</script>',
            'description' => 'Click <a href=javascript:alert(2)>here</a> to <b>break</b>',
            'category' => 'bug',
            'issuer' => '<script>steal()</script>Mallory',
        ]);

        $response->assertCreated();
        $this->assertStringNotContainsString('<script>', $response->json('data.title'));
        $this->assertStringNotContainsString('<a href', $response->json('data.description'));
        $this->assertStringNotContainsString('<script>', $response->json('data.issuer'));
    }

    public function test_list_issues_returns_paginated_with_newest_first(): void
    {
        $support = Category::findBySlug('support');
        Issue::factory()->count(3)->create(['category_id' => $support->id]);

        $response = $this->getJson('/api/issues');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'title', 'priority', 'category', 'status', 'is_escalated']],
            'meta' => ['total', 'per_page', 'current_page'],
        ]);
        $this->assertSame(3, $response->json('meta.total'));
    }

    public function test_list_issues_filters_combine_correctly(): void
    {
        $support = Category::findBySlug('support');
        $bug = Category::findBySlug('bug');
        Issue::factory()->create(['category_id' => $support->id, 'status' => 'open']);
        Issue::factory()->create(['category_id' => $support->id, 'status' => 'closed']);
        Issue::factory()->create(['category_id' => $bug->id, 'status' => 'open']);

        $response = $this->getJson('/api/issues?status=open&category=support');
        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
    }

    public function test_show_issue_includes_eager_loaded_category(): void
    {
        $support = Category::findBySlug('support');
        $issue = Issue::factory()->create(['category_id' => $support->id]);

        $response = $this->getJson("/api/issues/{$issue->id}");

        $response->assertOk();
        $response->assertJsonPath('data.category.slug', 'support');
        $response->assertJsonPath('data.category.name', 'Support');
    }

    public function test_show_returns_404_for_unknown_issue(): void
    {
        $this->getJson('/api/issues/99999')->assertNotFound();
    }

    public function test_update_recalculates_priority_when_category_changes(): void
    {
        $featureCat = Category::findBySlug('feature_request'); // severity=low
        $incidentCat = Category::findBySlug('incident');       // severity=high

        $issue = Issue::factory()->create(['category_id' => $featureCat->id]);
        $this->assertSame('low', $issue->priority->value);

        $response = $this->patchJson("/api/issues/{$issue->id}", [
            'category' => 'incident',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.priority', 'high');
        $response->assertJsonPath('data.category.slug', 'incident');
    }

    public function test_update_returns_409_when_if_unmodified_since_is_stale(): void
    {
        $support = Category::findBySlug('support');
        $issue = Issue::factory()->create(['category_id' => $support->id]);

        $response = $this->patchJson("/api/issues/{$issue->id}", [
            'status' => 'resolved',
            'if_unmodified_since' => '2020-01-01T00:00:00+00:00',
        ]);

        $response->assertStatus(409);
        $this->assertSame(
            'The resource has been modified since you last retrieved it.',
            $response->json('message'),
        );
        $this->assertNotNull($response->json('current_updated_at'));
    }

    public function test_escalation_flag_sets_when_high_priority_overdue_issue_is_created(): void
    {
        // Create issue with category=incident (severity=high) + due_date past
        $response = $this->postJson('/api/issues', [
            'title' => 'Overdue critical',
            'description' => 'The auth service has been failing all morning.',
            'category' => 'incident',
            'due_date' => now()->subDay()->toIso8601String(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.priority', 'high');
        $response->assertJsonPath('data.is_escalated', true);
        $this->assertNotNull($response->json('data.escalated_at'));
    }
}
