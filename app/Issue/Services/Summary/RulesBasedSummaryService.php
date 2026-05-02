<?php

namespace App\Issue\Services\Summary;

use App\Issue\Models\PlaybookEntry;

class RulesBasedSummaryService implements SummaryServiceInterface
{
    /**
     * @return array{summary: string, suggested_action: string, matched_playbook_slug: ?string, confidence: int, source: 'rules'|'fallback'}
     */
    public function generate(string $description): array
    {
        $haystack = mb_strtolower($description);
        $entries = PlaybookEntry::query()->get();

        $bestEntry = null;
        $bestScore = 0;

        foreach ($entries as $entry) {
            $score = $this->scoreEntry($haystack, $entry);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestEntry = $entry;
            }
        }

        if ($bestEntry !== null && $bestScore > 0) {
            return [
                'summary' => $bestEntry->summary_template,
                'suggested_action' => $bestEntry->suggested_action,
                'matched_playbook_slug' => $bestEntry->slug,
                'confidence' => $bestScore,
                'source' => 'rules',
            ];
        }

        return [
            'summary' => $this->genericSummary($description),
            'suggested_action' => 'Review issue and route to the appropriate team for manual triage.',
            'matched_playbook_slug' => null,
            'confidence' => 0,
            'source' => 'fallback',
        ];
    }

    private function scoreEntry(string $haystack, PlaybookEntry $entry): int
    {
        $score = 0;
        foreach ($entry->triggers as $trigger) {
            if ($trigger === '') {
                continue;
            }
            if (str_contains($haystack, mb_strtolower($trigger))) {
                $score++;
            }
        }
        return $score;
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
