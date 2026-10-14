<?php

namespace App\Providers;

use App\Services\Ai\AnthropicLlmClient;
use App\Services\Ai\Contracts\LlmClient;
use App\Services\Ai\FakeLlmClient;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LlmClient::class, fn () => match (config('services.llm.driver')) {
            'fake' => new FakeLlmClient,
            default => new AnthropicLlmClient(
                (string) config('services.llm.anthropic.key'),
                (string) config('services.llm.anthropic.model'),
            ),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
