<?php

namespace TimoDeWinter\LaravelDocker;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelDockerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-docker')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_docker_table');
    }
}
