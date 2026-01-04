<?php

declare(strict_types=1);

namespace Croustibat\ComposerCheck\Laravel;

use Illuminate\Support\ServiceProvider;

class ComposerCheckServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/composer-check.php',
            'composer-check'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ComposerCheckCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../../config/composer-check.php' => config_path('composer-check.php'),
            ], 'composer-check-config');
        }
    }
}
