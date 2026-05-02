<?php

namespace App\Issue\Services\Summary;

interface SummaryServiceInterface
{
    /**
     * Generate a summary and suggested next action for an issue description.
     *
     * @return array{
     *     summary: string,
     *     suggested_action: string,
     *     matched_playbook_slug: ?string,
     *     confidence: int,
     *     source: 'rules'|'llm'|'fallback'
     * }
     */
    public function generate(string $description): array;
}
