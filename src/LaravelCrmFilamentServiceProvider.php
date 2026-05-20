<?php

namespace VentureDrake\LaravelCrmFilament;

use Illuminate\Filesystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Product;
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
        // Core CRM declares `labels()` on Lead/Deal/Quote/Order/Invoice/PurchaseOrder/Person/Organization/Customer
        // but not on Product or Delivery, even though the same polymorphic
        // `labelables` pivot supports them. Inject the relation via
        // `Model::resolveRelationUsing()` so Filament's
        // `->relationship('labels', 'name')` works on those resources too.
        $prefix = config('laravel-crm.db_table_prefix', 'crm_');
        $morphName = $prefix . 'labelable';

        Product::resolveRelationUsing('labels', function ($model) use ($morphName) {
            return $model->morphToMany(Label::class, $morphName);
        });

        Delivery::resolveRelationUsing('labels', function ($model) use ($morphName) {
            return $model->morphToMany(Label::class, $morphName);
        });

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
