<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Labels\Pages;

use Filament\Actions;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\Labels\LabelResource;

class ViewLabel extends ViewRecord
{
    protected static string $resource = LabelResource::class;

    public function getTitle(): string
    {
        return __('laravel-crm-filament::labels.sales.label') . ': ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            LabelResource::backToIndexAction(),
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
                        TextEntry::make('name')
                            ->label(__('laravel-crm-filament::labels.fields.name'))
                            ->state($record?->name),
                        ColorEntry::make('hex')
                            ->label(__('laravel-crm-filament::labels.fields.color'))
                            ->state($record?->hex ? '#' . ltrim($record->hex, '#') : null),
                        TextEntry::make('description')
                            ->label(__('laravel-crm-filament::labels.fields.description'))
                            ->state($record?->description)
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(['lg' => 1]),
                Section::make(__('laravel-crm-filament::labels.sections.usage'))
                    ->schema([
                        TextEntry::make('leads_count')
                            ->label(__('laravel-crm-filament::labels.sales.leads'))
                            ->badge()
                            ->state($record ? $record->leads()->count() : 0),
                        TextEntry::make('deals_count')
                            ->label(__('laravel-crm-filament::labels.sales.deals'))
                            ->badge()
                            ->state($record ? $record->deals()->count() : 0),
                        TextEntry::make('people_count')
                            ->label(__('laravel-crm-filament::labels.sales.people'))
                            ->badge()
                            ->state($record ? $record->people()->count() : 0),
                        TextEntry::make('organizations_count')
                            ->label(__('laravel-crm-filament::labels.sales.organizations'))
                            ->badge()
                            ->state($record ? $record->organizations()->count() : 0),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
