<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Fields\Pages;

use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrmFilament\Resources\Fields\FieldResource;

class ViewField extends ViewRecord
{
    protected static string $resource = FieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FieldResource::backToIndexAction(),
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

        $optionsState = $record && in_array($record->type, ['select', 'select_multiple', 'radio', 'checkbox_multiple'], true)
            ? $record->fieldOptions->map(fn ($o) => $o->label ?? $o->value)->implode(', ')
            : null;

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make(__('laravel-crm-filament::labels.sections.details'))
                    ->schema([
                        TextEntry::make('type')
                            ->label(__('laravel-crm-filament::labels.fields.type'))
                            ->badge()
                            ->state($record?->type),
                        TextEntry::make('options')
                            ->label(__('laravel-crm-filament::labels.fields.options'))
                            ->state($optionsState)
                            ->visible(fn () => $optionsState !== null && $optionsState !== ''),
                        TextEntry::make('field_group')
                            ->label(__('laravel-crm-filament::labels.fields.group'))
                            ->state($record?->fieldGroup?->name),
                        TextEntry::make('required')
                            ->label(__('laravel-crm-filament::labels.fields.required'))
                            ->state($record?->required ? __('laravel-crm::lang.yes') : __('laravel-crm::lang.no')),
                        TextEntry::make('default')
                            ->label(__('laravel-crm-filament::labels.fields.default'))
                            ->state($record?->default),
                        TextEntry::make('system')
                            ->label(__('laravel-crm-filament::labels.fields.system'))
                            ->state($record?->system ? __('laravel-crm::lang.yes') : __('laravel-crm::lang.no')),
                        TextEntry::make('handle')
                            ->label(__('laravel-crm-filament::labels.fields.handle'))
                            ->state($record?->handle),
                    ])
                    ->columnSpan(['lg' => 1]),
                Section::make(__('laravel-crm-filament::labels.fields.attached_to'))
                    ->schema([
                        TextEntry::make('attached_to')
                            ->hiddenLabel()
                            ->badge()
                            ->state($record
                                ? $record->fieldModels
                                    ->map(fn ($fm) => Str::plural(class_basename($fm->model)))
                                    ->all()
                                : []),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
