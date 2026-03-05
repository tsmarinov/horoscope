<?php

namespace App\Providers;

use App\Services\AspectCalculator;
use App\Services\VariantPicker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AspectCalculator::class);
        $this->app->singleton(VariantPicker::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
