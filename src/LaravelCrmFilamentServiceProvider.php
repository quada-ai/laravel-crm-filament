<?php

namespace VentureDrake\LaravelCrmFilament;

use Illuminate\Filesystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use VentureDrake\LaravelCrmFilament\Console\InstallCommand;

class LaravelCrmFilamentServiceProvider extends PackageServiceProvider
{
    public static string $name = 'laravel-crm-filament';

    public static string $viewNamespace = 'laravel-crm-filament';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasCommands($this->getCommands());

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        //
    }

    public function packageBooted(): void
    {
        // Publish PanelProvider stub used by the install command.
        if ($this->app->runningInConsole()) {
            $files = new Filesystem;

            if ($files->isDirectory(__DIR__ . '/../stubs')) {
                foreach ($files->files(__DIR__ . '/../stubs') as $file) {
                    $this->publishes([
                        $file->getRealPath() => base_path("stubs/laravel-crm-filament/{$file->getFilename()}"),
                    ], 'laravel-crm-filament-stubs');
                }
            }
        }
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            InstallCommand::class,
        ];
    }
}
