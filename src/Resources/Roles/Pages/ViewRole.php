<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Roles\Pages;

use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\Roles\RoleResource;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string
    {
        return __('laravel-crm-filament::labels.sales.role') . ': ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            RoleResource::backToIndexAction(),
            Actions\EditAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-pencil-square')
                ->visible(fn ($record) => ! in_array($record->name, ['Owner', 'Admin'], true)),
            Actions\DeleteAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-trash')
                ->requiresConfirmation()
                ->visible(fn ($record) => ! in_array($record->name, ['Owner', 'Admin'], true)),
        ];
    }

    public function content(Schema $schema): Schema
    {
        // Capture the record now (page has it bound by the time content() runs)
        // so the closures below can read it directly instead of relying on
        // Filament's parameter injection, which was firing with $record=null
        // when nested Section schemas were evaluated in some render phases.
        $record = $this->record;

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                // Left column: Details + Permissions stacked
                Group::make([
                    Section::make(__('laravel-crm-filament::labels.sections.details'))
                        ->schema([
                            TextEntry::make('description')
                                ->label(__('laravel-crm-filament::labels.fields.description'))
                                ->placeholder('—'),
                            TextEntry::make('crm_role_display')
                                ->label(__('laravel-crm-filament::labels.misc.crm_role'))
                                ->state($record?->crm_role ? 'Yes' : 'No'),
                        ]),
                    Section::make(__('laravel-crm-filament::labels.fields.permissions'))
                        ->schema([
                            TextEntry::make('permission_names')
                                ->hiddenLabel()
                                ->badge()
                                ->color('gray')
                                ->state($record ? $record->permissions()->pluck('name')->all() : [])
                                ->placeholder(__('laravel-crm-filament::labels.misc.none')),
                        ]),
                ])->columnSpan(['lg' => 1]),
                // Right column: Users
                Section::make(__('laravel-crm-filament::labels.fields.users'))
                    ->schema([
                        TextEntry::make('user_names')
                            ->hiddenLabel()
                            ->icon('heroicon-o-user')
                            ->listWithLineBreaks()
                            ->state($record ? $record->users()->pluck('name')->all() : [])
                            ->placeholder(__('laravel-crm-filament::labels.misc.none')),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
