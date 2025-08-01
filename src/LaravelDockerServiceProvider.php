<?php

namespace TimoDeWinter\LaravelDocker;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelDockerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-docker')
            ->hasInstallCommand(function (InstallCommand $installCommand) {
                $installCommand
                    ->endWith(function (InstallCommand $installCommand) {
                        $name = $installCommand->ask('What name would you like to give your container?');

                        $sluggedName = str($name)->slug()->value();
                        $envName = str($name)->slug('_')->upper()->value();

                        $installCommand->callSilently('vendor:publish', [
                            '--provider' => 'TimoDeWinter\LaravelDocker\LaravelDockerServiceProvider',
                        ]);

                        // First we rename the reference in the Dockerfile
                        $this->replaceInFile(file: base_path('.docker/dev/Dockerfile'), replacements: [
                            'timodewinter-laravel-docker' => $sluggedName,
                        ]);

                        // Now we rename the references in the compose file
                        $this->replaceInFile(file: base_path('compose.dev.yml'), replacements: [
                            'timodewinter-laravel-docker' => $sluggedName,
                        ]);

                        // Now we rename all references in the binary file
                        $this->replaceInFile(file: base_path('binary'), replacements: [
                            'timodewinter-laravel-docker' => $sluggedName,
                            'TDW_LARAVEL_DOCKER' => $envName,
                        ]);

                        // Last we rename the binary file to the sluggedname
                        rename(base_path('binary'), $sluggedName);
                    });
            });
    }

    public function registeringPackage(): void
    {
        $this->publishes([
            __DIR__ . '/../resources/stubs/.docker' => base_path('.docker'),
            __DIR__ . '/../resources/stubs/root' => base_path(),
        ], 'docker');
    }

    private function replaceInFile(string $file, array $replacements): void
    {
        $contents = file_get_contents($file);

        file_put_contents(
            $file,
            str_replace(
                array_keys($replacements),
                array_values($replacements),
                $contents
            )
        );
    }
}
