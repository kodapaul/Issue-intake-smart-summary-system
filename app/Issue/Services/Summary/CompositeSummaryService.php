<?php

namespace App\Issue\Services\Summary;

use Illuminate\Support\Facades\Log;
use Throwable;

class CompositeSummaryService implements SummaryServiceInterface
{
    /**
     * Minimum trigger-match score for the rules-based result to be trusted
     * without escalating to the LLM.
     */
    public const CONFIDENCE_THRESHOLD = 2;

    public function __construct(
        private readonly RulesBasedSummaryService $rules,
        private readonly ?LlmSummaryService $llm = null,
    ) {}

    /**
     * @return array{summary: string, suggested_action: string, matched_playbook_slug: ?string, confidence: int, source: 'rules'|'llm'|'fallback'}
     */
    public function generate(string $description): array
    {
        $rulesResult = $this->rules->generate($description);

        if ($rulesResult['confidence'] >= self::CONFIDENCE_THRESHOLD) {
            return $rulesResult;
        }

        if ($this->llm !== null) {
            try {
                return $this->llm->generate($description);
            } catch (Throwable $e) {
                Log::channel('api')->warning('summary.llm.failed', [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $rulesResult;
    }
}
