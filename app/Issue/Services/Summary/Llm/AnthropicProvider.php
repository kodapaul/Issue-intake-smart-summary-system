<?php

namespace App\Issue\Services\Summary\Llm;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicProvider extends AbstractLlmProvider
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const ANTHROPIC_VERSION = '2023-06-01';

    protected function call(string $userMessage): string
    {
        $apiKey = config('services.anthropic.api_key');
        $model = config('services.anthropic.model', 'claude-haiku-4-5-20251001');

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::ANTHROPIC_VERSION,
            'content-type' => 'application/json',
        ])
            ->timeout(self::TIMEOUT_SECONDS)
            ->post(self::ENDPOINT, [
                'model' => $model,
                'max_tokens' => self::MAX_OUTPUT_TOKENS,
                'system' => self::SYSTEM_PROMPT,
                'messages' => [
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Anthropic HTTP {$response->status()}: " . $response->body(),
            );
        }

        $text = $response->json('content.0.text');
        if (! is_string($text)) {
            throw new RuntimeException('Anthropic response missing content.0.text');
        }

        return $text;
    }
}
