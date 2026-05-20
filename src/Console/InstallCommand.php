<?php

namespace VentureDrake\LaravelCrmFilament\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallCommand extends Command
{
    protected $signature = 'laravelcrm:filament-install
        {--force : Overwrite an existing CrmPanelProvider.}';

    protected $description = 'Install the Laravel CRM Filament panel (publishes CrmPanelProvider at app/Providers/Filament/CrmPanelProvider.php).';

    public function handle(Filesystem $files): int
    {
        $stub = __DIR__ . '/../../stubs/CrmPanelProvider.php.stub';
        $target = app_path('Providers/Filament/CrmPanelProvider.php');

        if (! $files->exists($stub)) {
            $this->error("Missing stub: {$stub}");

            return self::FAILURE;
        }

        if ($files->exists($target) && ! $this->option('force')) {
            $this->warn("CrmPanelProvider already exists at {$target}. Re-run with --force to overwrite.");

            return self::SUCCESS;
        }

        $files->ensureDirectoryExists(dirname($target));
        $files->put($target, $files->get($stub));
        $this->info("Published CrmPanelProvider to {$target}");

        $this->registerProvider($files);

        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Add the FilamentUser interface + canAccessPanel() to App\Models\User');
        $this->line('     (or rely on the HasCrmAccess trait once it implements FilamentUser).');
        $this->line('  2. Visit /admin to view your panel.');

        return self::SUCCESS;
    }

    protected function registerProvider(Filesystem $files): void
    {
        $providersFile = base_path('bootstrap/providers.php');

        if (! $files->exists($providersFile)) {
            $this->warn('bootstrap/providers.php not found; register App\\Providers\\Filament\\CrmPanelProvider manually.');

            return;
        }

        $contents = $files->get($providersFile);
        $providerClass = 'App\\Providers\\Filament\\CrmPanelProvider::class';

        if (str_contains($contents, 'CrmPanelProvider::class')) {
            $this->line('CrmPanelProvider already registered in bootstrap/providers.php.');

            return;
        }

        $updated = preg_replace(
            '/return\s*\[\s*\n/',
            "return [\n    {$providerClass},\n",
            $contents,
            1,
            $count
        );

        if ($count === 0 || $updated === null) {
            $this->warn('Could not auto-register CrmPanelProvider in bootstrap/providers.php; add it manually.');

            return;
        }

        $files->put($providersFile, $updated);
        $this->info('Registered CrmPanelProvider in bootstrap/providers.php.');
    }
}
