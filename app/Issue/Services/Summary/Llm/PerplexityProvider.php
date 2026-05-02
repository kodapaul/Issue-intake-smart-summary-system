<?php

namespace App\Issue\Services\Summary\Llm;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PerplexityProvider extends AbstractLlmProvider
{
    private const ENDPOINT = 'https://api.perplexity.ai/chat/completions';

    protected function call(string $userMessage): string
    {
        $apiKey = config('services.perplexity.api_key');
        $model = config('services.perplexity.model', 'sonar');

        $response = Http::withToken($apiKey)
            ->timeout(self::TIMEOUT_SECONDS)
            ->post(self::ENDPOINT, [
                'model' => $model,
                'max_tokens' => self::MAX_OUTPUT_TOKENS,
                'temperature' => 0,
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Perplexity HTTP {$response->status()}: " . $response->body(),
            );
        }

        $text = $response->json('choices.0.message.content');
        if (! is_string($text)) {
            throw new RuntimeException('Perplexity response missing choices.0.message.content');
        }

        return $text;
    }
}
