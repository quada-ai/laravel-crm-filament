<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Orders;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\CreateOrder;
use VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\EditOrder;
use VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\ListOrders;
use VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\ViewOrder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $slug = 'orders';

    protected static ?string $recordTitleAttribute = 'order_id';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 51;

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
        return $schema->components([
            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('reference')->maxLength(100),
                Forms\Components\TextInput::make('currency')
                    ->maxLength(3)
                    ->default(config('laravel-crm.default_currency', 'USD')),
            ]),

            // Line items
            Forms\Components\Repeater::make('products')
                ->label('Line items')
                ->schema([
                    Forms\Components\Hidden::make('order_product_id'),
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

            // Addresses
            Forms\Components\Repeater::make('addresses')
                ->label('Addresses')
                ->schema([
                    Forms\Components\Hidden::make('id'),
                    Grid::make(2)->schema([
                        Forms\Components\Select::make('address_type_id')
                            ->label('Type')
                            ->options(fn () => AddressType::query()->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\TextInput::make('name')->maxLength(255),
                    ]),
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('line1')->label('Line 1')->maxLength(255),
                        Forms\Components\TextInput::make('line2')->label('Line 2')->maxLength(255),
                        Forms\Components\TextInput::make('line3')->label('Line 3')->maxLength(255),
                    ]),
                    Grid::make(4)->schema([
                        Forms\Components\TextInput::make('city')->maxLength(255),
                        Forms\Components\TextInput::make('state')->maxLength(255),
                        Forms\Components\TextInput::make('code')->label('Postal code')->maxLength(20),
                        Forms\Components\TextInput::make('country')->maxLength(255),
                    ]),
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('contact')->maxLength(255),
                        Forms\Components\TextInput::make('phone')->maxLength(50),
                    ]),
                ])
                ->addActionLabel('Add address')
                ->defaultItems(0)
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

            Forms\Components\Select::make('user_owner_id')
                ->label('Owner')
                ->relationship('ownerUser', 'name')
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('total')
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label('Owner')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
