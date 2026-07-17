<?php

namespace VentureDrake\LaravelCrmFilament\Console;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\PanelRegistry;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Throwable;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;

class InstallCommand extends Command
{
    protected $signature = 'laravelcrm:filament-install
        {--mode= : Install mode: crm (standalone panel) or inject (into an existing panel).}
        {--panel= : Target panel id when using --mode=inject.}
        {--force : Overwrite an existing CrmPanelProvider.}';

    protected $description = 'Install the Laravel CRM Filament panel (publishes CrmPanelProvider at app/Providers/Filament/CrmPanelProvider.php).';

    public function handle(Filesystem $files): int
    {
        $mode = $this->option('mode');
        $panelId = $this->option('panel');

        if ($mode === null) {
            [$mode, $panelId] = $this->resolveModeAndPanel($panelId);
        } elseif ($mode === 'inject' && $panelId === null) {
            $panelId = $this->promptForTargetPanel();
        }

        if ($mode === 'inject') {
            return $this->installInjectMode($files, $panelId);
        }

        return $this->installCrmMode($files);
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function resolveModeAndPanel(?string $panelId): array
    {
        $panels = $this->detectPanels();

        if ($panels === []) {
            return ['crm', $panelId];
        }

        $isCrmOnly = count($panels) === 1 && $panels[0]['id'] === 'crm';

        if ($isCrmOnly) {
            return ['crm', $panelId];
        }

        $this->line('Detected Filament panels:');
        $this->table(
            ['ID', 'Path', 'Resources', 'Provider'],
            array_map(
                static fn (array $panel): array => [
                    $panel['id'],
                    $panel['path'],
                    (string) $panel['resources'],
                    $panel['provider'] ?? '(unknown)',
                ],
                $panels
            )
        );

        $mode = $this->choice(
            'How would you like to install the CRM?',
            ['crm', 'inject'],
            'crm'
        );

        if ($mode === 'inject' && $panelId === null) {
            $panelId = $this->promptForTargetPanel($panels);
        }

        return [$mode, $panelId];
    }

    /**
     * @param  array<int, array{id: string, path: string, resources: int, provider: ?string}>|null  $panels
     */
    private function promptForTargetPanel(?array $panels = null): ?string
    {
        $panels ??= $this->detectPanels();
        $targets = array_values(array_filter($panels, static fn (array $p): bool => $p['id'] !== 'crm'));

        if ($targets === []) {
            $this->error('No non-CRM Filament panels detected to inject into.');

            return null;
        }

        $options = array_column($targets, 'id');

        return $this->choice(
            'Which panel should the CRM be injected into?',
            $options,
            $options[0]
        );
    }

    /**
     * @return array<int, array{id: string, path: string, resources: int, provider: ?string}>
     */
    private function detectPanels(): array
    {
        $registry = app(PanelRegistry::class);

        $providerByPanelId = [];
        foreach (app()->getProviders(PanelProvider::class) as $provider) {
            try {
                $panel = $provider->panel(Panel::make());
                $providerByPanelId[$panel->getId()] = get_class($provider);
            } catch (Throwable) {
                // Skip providers that can't be probed outside their normal registration path.
            }
        }

        $descriptors = [];
        foreach ($registry->all() as $panel) {
            $descriptors[] = [
                'id' => $panel->getId(),
                'path' => $panel->getPath(),
                'resources' => count($panel->getResources()),
                'provider' => $providerByPanelId[$panel->getId()] ?? null,
            ];
        }

        return $descriptors;
    }

    private function installCrmMode(Filesystem $files): int
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

        $this->maybeDisableLegacyCrmUi($files);

        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Add the FilamentUser interface + canAccessPanel() to App\Models\User');
        $this->line('     (or rely on the HasCrmAccess trait once it implements FilamentUser).');
        $this->line('  2. Visit /crm to view your panel.');

        return self::SUCCESS;
    }

    private function maybeDisableLegacyCrmUi(Filesystem $files): void
    {
        if (! config('laravel-crm.user_interface', true)) {
            return;
        }

        $envLine = 'LARAVEL_CRM_USER_INTERFACE=false';
        $confirmed = $this->confirm(
            'Add LARAVEL_CRM_USER_INTERFACE=false to your .env now (disables the legacy /crm Livewire UI so the Filament CRM panel can take over /crm)?'
        );

        if (! $confirmed) {
            $this->line("Add this line to your .env manually: {$envLine}");

            return;
        }

        $envPath = base_path('.env');

        if (! $files->exists($envPath)) {
            $this->warn('.env file not found; add this line manually: ' . $envLine);

            return;
        }

        $contents = $files->get($envPath);

        if (preg_match('/^\s*LARAVEL_CRM_USER_INTERFACE\s*=/m', $contents) === 1) {
            $this->line('LARAVEL_CRM_USER_INTERFACE is already set in .env; leaving as-is.');

            return;
        }

        $suffix = (str_ends_with($contents, "\n") || $contents === '') ? '' : "\n";
        $files->put($envPath, $contents . $suffix . $envLine . "\n");
        $this->info('Appended LARAVEL_CRM_USER_INTERFACE=false to .env.');
    }

    private function installInjectMode(Filesystem $files, ?string $panelId): int
    {
        $this->error('Inject mode is not implemented yet.');

        return self::FAILURE;
    }

    /**
     * Compare the plugin's would-be resource list against a target panel's resources
     * and return one descriptor per colliding `getSlug()`. Panel id/path collisions are
     * intentionally NOT surfaced here — this only covers resource-slug conflicts.
     *
     * @return array<int, array{slug: string, existing: class-string, plugin: class-string}>
     */
    private function detectConflicts(Panel $target): array
    {
        $pluginResources = LaravelCrmPlugin::make()->getResources();

        $existingBySlug = [];
        foreach ($target->getResources() as $existing) {
            $existingBySlug[$existing::getSlug()] = $existing;
        }

        $conflicts = [];
        foreach ($pluginResources as $pluginResource) {
            $slug = $pluginResource::getSlug();

            if (! isset($existingBySlug[$slug])) {
                continue;
            }

            $existing = $existingBySlug[$slug];

            // A resource class registered on both sides isn't really a collision;
            // only flag when two distinct classes claim the same slug.
            if ($existing === $pluginResource) {
                continue;
            }

            $conflicts[] = [
                'slug' => $slug,
                'existing' => $existing,
                'plugin' => $pluginResource,
            ];
        }

        return $conflicts;
    }

    /**
     * @param  array<int, array{slug: string, existing: class-string, plugin: class-string}>  $conflicts
     */
    private function renderConflictTable(array $conflicts): void
    {
        $this->table(
            ['Slug', 'Existing resource', 'Plugin resource'],
            array_map(
                static fn (array $conflict): array => [
                    $conflict['slug'],
                    $conflict['existing'],
                    $conflict['plugin'],
                ],
                $conflicts
            )
        );

        $this->line('Re-run with `--mode=crm` to publish a standalone /crm panel instead of injecting.');
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
