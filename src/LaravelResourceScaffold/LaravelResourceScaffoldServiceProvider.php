<?php

declare(strict_types=1);

namespace Renas\LaravelResourceScaffold;

use Illuminate\Support\ServiceProvider;
use Renas\LaravelResourceScaffold\Console\GenerateNewPageCommand;

class LaravelResourceScaffoldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // no-op
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateNewPageCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../../stubs' => base_path('stubs/laravel-resource-scaffold'),
            ], 'laravel-resource-scaffold-stubs');

            $this->publishes([
                __DIR__ . '/../../stubs' => base_path('stubs/inertia-scaffold'),
            ], 'inertia-scaffold-stubs');

            $this->publishes([
                __DIR__ . '/../../stubs' => base_path('stubs/inertia-page-generator'),
            ], 'inertia-page-generator-stubs');
        }
    }
}
