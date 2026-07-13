<?php

namespace VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages;

use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\RelationManagers\FieldGroupFieldsRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\FieldGroupResource;

class ViewFieldGroup extends ViewRecord
{
    protected static string $resource = FieldGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FieldGroupResource::backToIndexAction(),
            Actions\EditAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-pencil-square'),
            Actions\DeleteAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-trash'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        // Capture the record now (page has it bound by the time content() runs)
        // so the closures below can read it directly instead of relying on
        // Filament's parameter injection, which was firing with $record=null
        // when nested Section schemas were evaluated in some render phases.
        // See commit 54b6a24 for the recurring bug this pattern avoids.
        $record = $this->record;

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make(__('laravel-crm-filament::labels.sections.details'))
                    ->schema([
                        TextEntry::make('system')
                            ->label(__('laravel-crm-filament::labels.fields.system'))
                            ->badge()
                            ->state($record?->system ? __('laravel-crm::lang.yes') : __('laravel-crm::lang.no')),
                        TextEntry::make('handle')
                            ->label(__('laravel-crm-filament::labels.fields.handle'))
                            ->state($record?->handle),
                    ])
                    ->columnSpan(['lg' => 1]),
                Livewire::make(FieldGroupFieldsRelationManager::class, [
                    'ownerRecord' => $record,
                    'pageClass' => static::class,
                ])
                    ->key('field-group-fields-' . $record->getKey())
                    ->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
