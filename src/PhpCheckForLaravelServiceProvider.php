<?php

namespace Croustibat\PhpCheckForLaravel;

use Croustibat\PhpCheckForLaravel\Commands\PhpCheckCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PhpCheckForLaravelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('php-check-for-laravel')
            ->hasConfigFile()
            ->hasCommand(PhpCheckCommand::class);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(PhpCheckForLaravel::class, fn () => new PhpCheckForLaravel);
    }
}
