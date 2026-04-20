<?php

namespace Gigerit\LaravelCascadeDelete;

use Gigerit\LaravelCascadeDelete\Commands\CascadeDeleteCleanCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelCascadeDeleteServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-cascade-delete')
            ->hasConfigFile()
            ->hasCommand(CascadeDeleteCleanCommand::class);
    }
}
