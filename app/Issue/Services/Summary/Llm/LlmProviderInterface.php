<?php

namespace App\Issue\Services\Summary\Llm;

interface LlmProviderInterface
{
    /**
     * Classify a customer description against the playbook catalog.
     * Returns the matched slug, "none" if no match, or null on parse failure.
     *
     * @param  array<int, array{slug: string, name: string, description: string}>  $catalog
     */
    public function classify(string $description, array $catalog): ?string;
}
