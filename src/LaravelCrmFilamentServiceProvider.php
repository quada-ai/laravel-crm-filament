<?php

namespace VentureDrake\LaravelCrmFilament;

use Illuminate\Filesystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Console\InstallCommand;
use VentureDrake\LaravelCrmFilament\Models\Audit;

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

        // Core CRM's base Model does not implement OwenIt\Auditing\Auditable
        // even though the audits table ships with the install. Resolve an
        // `audits()` morphMany on every primary model so the
        // AuditsRelationManager can hang off the standard
        // `protected static string $relationship = 'audits'` contract.
        foreach (static::auditableModels() as $auditableModel) {
            $auditableModel::resolveRelationUsing('audits', function ($model) {
                return $model->morphMany(Audit::class, 'auditable');
            });
        }

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
    public static function auditableModels(): array
    {
        return [
            Lead::class,
            Deal::class,
            Quote::class,
            Order::class,
            Invoice::class,
            Delivery::class,
            PurchaseOrder::class,
            Person::class,
            Organization::class,
            Product::class,
        ];
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
