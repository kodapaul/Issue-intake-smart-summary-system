<?php

namespace App\Issue\Services\Summary;

use App\Issue\Models\PlaybookEntry;
use App\Issue\Services\Summary\Llm\LlmProviderInterface;
use Illuminate\Support\Facades\Log;

class LlmSummaryService implements SummaryServiceInterface
{
    public function __construct(
        private readonly LlmProviderInterface $provider,
    ) {}

    /**
     * @return array{summary: string, suggested_action: string, matched_playbook_slug: ?string, confidence: int, source: 'llm'|'fallback'}
     */
    public function generate(string $description): array
    {
        $entries = PlaybookEntry::query()->get();

        $catalog = $entries
            ->map(fn (PlaybookEntry $e): array => [
                'slug' => $e->slug,
                'name' => $e->name,
                'description' => $e->description,
            ])
            ->all();

        $slug = $this->provider->classify($description, $catalog);

        if ($slug !== null && $slug !== 'none') {
            $matched = $entries->firstWhere('slug', $slug);
            if ($matched !== null) {
                return [
                    'summary' => $matched->summary_template,
                    'suggested_action' => $matched->suggested_action,
                    'matched_playbook_slug' => $matched->slug,
                    'confidence' => 99,
                    'source' => 'llm',
                ];
            }

            Log::channel('api')->warning('summary.llm.unknown_slug', [
                'returned_slug' => $slug,
                'provider' => $this->provider::class,
            ]);
        }

        return [
            'summary' => $this->genericSummary($description),
            'suggested_action' => 'Review issue and route to the appropriate team for manual triage.',
            'matched_playbook_slug' => null,
            'confidence' => 0,
            'source' => 'fallback',
        ];
    }

    private function genericSummary(string $description): string
    {
        $first = strtok($description, ".!?\n") ?: $description;
        $first = trim($first);

        return mb_strlen($first) > 150
            ? mb_substr($first, 0, 147) . '...'
            : $first;
    }
}
