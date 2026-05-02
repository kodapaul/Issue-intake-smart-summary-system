<?php

namespace App\Issue\Services\Summary\Llm;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiProvider extends AbstractLlmProvider
{
    private const ENDPOINT_BASE = 'https://generativelanguage.googleapis.com/v1beta/models';

    protected function call(string $userMessage): string
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.0-flash');

        $endpoint = self::ENDPOINT_BASE . "/{$model}:generateContent?key=" . urlencode((string) $apiKey);

        $response = Http::timeout(self::TIMEOUT_SECONDS)
            ->post($endpoint, [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => self::SYSTEM_PROMPT],
                    ],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $userMessage]],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0,
                    'maxOutputTokens' => self::MAX_OUTPUT_TOKENS,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Gemini HTTP {$response->status()}: " . $response->body(),
            );
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        if (! is_string($text)) {
            throw new RuntimeException('Gemini response missing candidates.0.content.parts.0.text');
        }

        return $text;
    }
}
