<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\TaxRates;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\TaxRates\Pages\CreateTaxRate;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\TaxRates\Pages\EditTaxRate;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\TaxRates\Pages\ListTaxRates;

class TaxRateResource extends Resource
{
    protected static ?string $model = TaxRate::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'tax-rates';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-percent-badge';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('rate')
                    ->numeric()
                    ->required()
                    ->suffix('%'),
                Forms\Components\TextInput::make('tax_type')
                    ->label(__('laravel-crm-filament::labels.fields.type'))
                    ->placeholder('GST / VAT / Sales tax')
                    ->maxLength(50),
            ]),
            Forms\Components\Toggle::make('default')->label(__('laravel-crm-filament::labels.money.default_tax_rate')),
            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('rate')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('tax_type')->label(__('laravel-crm-filament::labels.fields.type'))->toggleable(),
                Tables\Columns\IconColumn::make('default')->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxRates::route('/'),
            'create' => CreateTaxRate::route('/create'),
            'edit' => EditTaxRate::route('/{record}/edit'),
        ];
    }
}
