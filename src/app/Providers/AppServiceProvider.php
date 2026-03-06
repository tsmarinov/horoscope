<?php

namespace App\Providers;

use Anthropic\Client as AnthropicClient;
use App\Contracts\AiProvider;
use App\Services\Ai\ClaudeProvider;
use App\Services\AspectCalculator;
use App\Services\HouseCalculator;
use App\Services\ReportBuilder;
use App\Services\VariantPicker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HouseCalculator::class);
        $this->app->singleton(AspectCalculator::class);
        $this->app->singleton(VariantPicker::class);

        $this->app->singleton(AiProvider::class, function () {
            $provider = config('astrology.ai.provider', 'claude');
            $model    = config('astrology.ai.model', 'claude-sonnet-4-6');

            return match ($provider) {
                'claude' => new ClaudeProvider(
                    client: new AnthropicClient(apiKey: env('ANTHROPIC_API_KEY', '')),
                    model: $model,
                ),
                default  => throw new \InvalidArgumentException("Unknown AI provider: {$provider}"),
            };
        });

        $this->app->singleton(ReportBuilder::class, function ($app) {
            return new ReportBuilder(
                aspectCalculator: $app->make(AspectCalculator::class),
                variantPicker:    $app->make(VariantPicker::class),
                aiProvider:       $app->make(AiProvider::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
