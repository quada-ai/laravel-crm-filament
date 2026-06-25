<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Features\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Services\FeatureService;
use VentureDrake\LaravelCrmFilament\Resources\Features\FeatureResource;

class CreateFeature extends CreateRecord
{
    protected static string $resource = FeatureResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string | Htmlable
    {
        return __('laravel-crm-filament::labels.actions.submit_feature');
    }

    protected function getHeaderActions(): array
    {
        return [
            FeatureResource::backToIndexAction(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('description')
                        ->rows(5)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('feature_status_id')
                        ->label(__('laravel-crm-filament::labels.fields.status'))
                        ->options(fn () => FeatureStatus::query()
                            ->orderBy('order')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload(),

                    Forms\Components\Toggle::make('is_public')
                        ->label(__('laravel-crm-filament::labels.fields.publicly_visible_to_portal_users'))
                        ->default(false),
                ]),
        ]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label(__('laravel-crm-filament::labels.actions.save'));
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = app(FeatureService::class)->create($data, auth()->user());
        FeatureResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}
