<?php

namespace App\Issue\Providers;

use App\Issue\Services\Summary\CompositeSummaryService;
use App\Issue\Services\Summary\Llm\AnthropicProvider;
use App\Issue\Services\Summary\Llm\GeminiProvider;
use App\Issue\Services\Summary\Llm\LlmProviderInterface;
use App\Issue\Services\Summary\Llm\OpenAIProvider;
use App\Issue\Services\Summary\Llm\PerplexityProvider;
use App\Issue\Services\Summary\LlmSummaryService;
use App\Issue\Services\Summary\RulesBasedSummaryService;
use App\Issue\Services\Summary\SummaryServiceInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class IssueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SummaryServiceInterface::class, function (Application $app): CompositeSummaryService {
            $provider = $this->resolveLlmProvider($app);
            $llm = $provider !== null
                ? new LlmSummaryService($provider)
                : null;

            return new CompositeSummaryService(
                rules: $app->make(RulesBasedSummaryService::class),
                llm: $llm,
            );
        });
    }

    private function resolveLlmProvider(Application $app): ?LlmProviderInterface
    {
        $providerName = config('services.llm.provider');
        if (! is_string($providerName) || $providerName === '') {
            return null;
        }

        $apiKeyConfigPath = match ($providerName) {
            'anthropic'  => 'services.anthropic.api_key',
            'openai'     => 'services.openai.api_key',
            'gemini'     => 'services.gemini.api_key',
            'perplexity' => 'services.perplexity.api_key',
            default      => null,
        };

        if ($apiKeyConfigPath === null) {
            return null;
        }

        $apiKey = config($apiKeyConfigPath);
        if (! is_string($apiKey) || $apiKey === '') {
            return null;
        }

        $providerClass = match ($providerName) {
            'anthropic'  => AnthropicProvider::class,
            'openai'     => OpenAIProvider::class,
            'gemini'     => GeminiProvider::class,
            'perplexity' => PerplexityProvider::class,
        };

        return $app->make($providerClass);
    }
}
