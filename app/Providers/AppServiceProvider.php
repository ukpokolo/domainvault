<?php

namespace App\Providers;

use App\Services\Ai\AnthropicLlmClient;
use App\Services\Ai\Contracts\LlmClient;
use App\Services\Ai\FakeLlmClient;
use App\Services\Checkout\Contracts\PaymentGateway;
use App\Services\Checkout\StripePaymentGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, fn () => new StripePaymentGateway(
            config('services.stripe.secret'),
            config('services.stripe.webhook_secret'),
        ));

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

        // 60 requests/minute per token (or IP for unauthenticated calls).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('api-login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
