<?php

namespace Croustibat\PhpCheckForLaravel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Croustibat\PhpCheckForLaravel\Commands\PhpCheckCommand;

class PhpCheckForLaravelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('php-check-for-laravel')
            ->hasCommand(PhpCheckCommand::class);
    }
}
