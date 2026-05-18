<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\CreateQuote;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\EditQuote;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\ListQuotes;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\ViewQuote;

class QuoteResource extends Resource
{
    use HasCrmCustomFields;

    protected static ?string $model = Quote::class;

    protected static ?string $slug = 'quotes';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup();
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),

            Grid::make(3)->schema([
                Forms\Components\TextInput::make('reference')->maxLength(100),
                Forms\Components\TextInput::make('currency')
                    ->maxLength(3)
                    ->default(config('laravel-crm.default_currency', 'USD')),
                Forms\Components\Select::make('pipeline_stage_id')
                    ->label('Stage')
                    ->options(fn () => PipelineStage::query()->orderBy('order')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
            ]),

            Grid::make(2)->schema([
                Forms\Components\DatePicker::make('issue_at')->label('Issue date'),
                Forms\Components\DatePicker::make('expire_at')->label('Expiry date'),
            ]),

            // Line items
            Forms\Components\Repeater::make('products')
                ->label('Line items')
                ->schema([
                    Forms\Components\Hidden::make('quote_product_id'),
                    Forms\Components\Select::make('id')
                        ->label('Product')
                        ->options(fn () => Product::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            $product = Product::find($state);
                            if ($product) {
                                $price = $product->getDefaultPrice();
                                $set('unit_price', $price ? $price->price / 100 : 0);
                            }
                        }),
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->default(1)
                        ->minValue(0)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                            $set('amount', (float) $state * (float) $get('unit_price'));
                        }),
                    Forms\Components\TextInput::make('unit_price')
                        ->label('Unit price')
                        ->numeric()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                            $set('amount', (float) $state * (float) $get('quantity'));
                        }),
                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->readOnly(),
                    Forms\Components\TextInput::make('comments')
                        ->maxLength(255),
                ])
                ->columns(5)
                ->addActionLabel('Add line item')
                ->defaultItems(0)
                ->reorderable()
                ->columnSpanFull(),

            // Totals
            Grid::make(4)->schema([
                Forms\Components\TextInput::make('sub_total')
                    ->label('Subtotal')
                    ->numeric(),
                Forms\Components\TextInput::make('discount')
                    ->numeric(),
                Forms\Components\TextInput::make('tax')
                    ->numeric(),
                Forms\Components\TextInput::make('total')
                    ->numeric(),
            ]),

            Forms\Components\Textarea::make('terms')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Select::make('user_owner_id')
                ->label('Owner')
                ->relationship('ownerUser', 'name')
                ->searchable()
                ->preload(),
        ];

        if ($customFields = static::crmCustomFieldsSection(Quote::class)) {
            $components[] = $customFields;
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quote_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('total')
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('pipelineStage.name')
                    ->label('Stage')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expire_at')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('pipeline_stage_id')
                    ->label('Stage')
                    ->options(fn () => PipelineStage::query()->orderBy('order')->pluck('name', 'id')),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

public static function getGloballySearchableAttributes(): array
    {
        return ['quote_id', 'title'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return (string) ($record->title ?? $record->getKey());
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter(['ID' => $record->quote_id]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'create' => CreateQuote::route('/create'),
            'view' => ViewQuote::route('/{record}'),
            'edit' => EditQuote::route('/{record}/edit'),
        ];
    }
}

