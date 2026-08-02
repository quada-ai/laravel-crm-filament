<?php

namespace VentureDrake\LaravelCrmFilament\Console;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'laravelcrm:filament-publish
        {--tag=* : Publish group tags (resources, pages, widgets, translations, views, stubs, all)}
        {--force : Overwrite existing published files}';

    protected $description = 'Publish Laravel CRM Filament resources, pages, widgets, views, and translations.';

    public function handle(): int
    {
        $tags = (array) $this->option('tag');

        if ($tags === []) {
            $choice = $this->choice(
                'What would you like to publish?',
                ['all', 'resources', 'pages', 'widgets', 'translations', 'views', 'stubs'],
                'all'
            );
            $tags = [$choice];
        }

        if (in_array('all', $tags, true)) {
            $tags = ['resources', 'pages', 'widgets', 'translations', 'views', 'stubs'];
        }

        $force = (bool) $this->option('force');

        foreach ($tags as $tag) {
            $fullTag = str_starts_with($tag, 'laravel-crm-filament-')
                ? $tag
                : 'laravel-crm-filament-' . $tag;

            $params = ['--tag' => $fullTag];
            if ($force) {
                $params['--force'] = true;
            }

            $this->info("Publishing {$fullTag}...");
            $this->call('vendor:publish', $params);
        }

        $this->info('Publishing completed.');

        return self::SUCCESS;
    }
}
