<?php

namespace App\Issue\Services\Summary\Llm;

abstract class AbstractLlmProvider implements LlmProviderInterface
{
    protected const SYSTEM_PROMPT = <<<TXT
You are an expert customer support triage agent with deep knowledge of customer support patterns across e-commerce, SaaS, and consumer-facing applications. Your job is to classify a single customer-submitted issue description against the company's support playbook.

You are precise, deterministic, and cautious. Output rules — these are absolute:
- Return ONLY the matching playbook slug, in lowercase with underscores (e.g., promo_codes).
- If no entry meaningfully matches the description, return exactly "none".
- Never include explanations, markdown, code fences, punctuation, quotes, or any other text.
- Your entire response is a single token: the slug, or "none".
TXT;

    protected const TIMEOUT_SECONDS = 10;
    protected const MAX_OUTPUT_TOKENS = 32;

    /**
     * @param  array<int, array{slug: string, name: string, description: string}>  $catalog
     */
    public function classify(string $description, array $catalog): ?string
    {
        $userMessage = $this->buildUserMessage($description, $catalog);
        $rawResponse = $this->call($userMessage);

        return $this->extractSlug($rawResponse);
    }

    abstract protected function call(string $userMessage): string;

    /**
     * @param  array<int, array{slug: string, name: string, description: string}>  $catalog
     */
    protected function buildUserMessage(string $description, array $catalog): string
    {
        $entries = collect($catalog)
            ->map(fn (array $e): string => "- {$e['slug']}: {$e['name']} — {$e['description']}")
            ->implode("\n");

        return <<<TXT
Available playbook entries:
{$entries}

Customer description:
\"\"\"
{$description}
\"\"\"

Return the matching slug, or "none".
TXT;
    }

    protected function extractSlug(string $text): ?string
    {
        $cleaned = trim(strtolower($text));
        $cleaned = trim($cleaned, ".,!?\"' \t\n\r");

        if ($cleaned === '') {
            return null;
        }

        if (preg_match('/^[a-z_]+$/', $cleaned) === 1) {
            return $cleaned;
        }

        $words = preg_split('/\s+/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($words) || $words === []) {
            return null;
        }

        $first = trim((string) $words[0], ".,!?\"' \t\n\r");
        return $first !== '' ? $first : null;
    }
}
