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
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                // Left column: Details + Permissions stacked
                Group::make([
                    Section::make(__('laravel-crm-filament::labels.sections.details'))
                        ->schema([
                            TextEntry::make('description')
                                ->label(__('laravel-crm-filament::labels.fields.description'))
                                ->placeholder('—'),
                            TextEntry::make('crm_role')
                                ->label(__('laravel-crm-filament::labels.misc.crm_role'))
                                ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        ]),
                    Section::make(__('laravel-crm-filament::labels.fields.permissions'))
                        ->schema([
                            TextEntry::make('permissions.name')
                                ->hiddenLabel()
                                ->badge()
                                ->color('gray')
                                ->placeholder(__('laravel-crm-filament::labels.misc.none')),
                        ]),
                ])->columnSpan(['lg' => 1]),
                // Right column: Users
                Section::make(__('laravel-crm-filament::labels.fields.users'))
                    ->schema([
                        TextEntry::make('users.name')
                            ->hiddenLabel()
                            ->icon('heroicon-o-user')
                            ->listWithLineBreaks()
                            ->placeholder(__('laravel-crm-filament::labels.misc.none')),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
