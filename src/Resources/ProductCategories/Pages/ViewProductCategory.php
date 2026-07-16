<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages;

use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\RelationManagers\ProductCategoryProductsRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\ProductCategoryResource;

class ViewProductCategory extends ViewRecord
{
    protected static string $resource = ProductCategoryResource::class;

    public function getTitle(): string
    {
        return __('laravel-crm-filament::labels.money.product_category') . ': ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            ProductCategoryResource::backToIndexAction(),
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
                        TextEntry::make('description')
                            ->label(__('laravel-crm-filament::labels.fields.description'))
                            ->state($record?->description)
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(['lg' => 1]),
                Livewire::make(ProductCategoryProductsRelationManager::class, [
                    'ownerRecord' => $record,
                    'pageClass' => static::class,
                ])
                    ->key('product-category-products-' . $record->getKey())
                    ->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
