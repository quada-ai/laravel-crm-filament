<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $slug = 'purchase-orders';

    protected static ?string $recordTitleAttribute = 'purchase_order_id';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 53;

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
            Grid::make(3)->schema([
                Forms\Components\Select::make('order_id')
                    ->label('Order')
                    ->options(fn () => Order::query()->orderByDesc('id')->limit(50)->get()->mapWithKeys(fn ($o) => [$o->id => $o->order_id])->all())
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('reference')->maxLength(100),
                Forms\Components\TextInput::make('currency')
                    ->maxLength(3)
                    ->default(config('laravel-crm.default_currency', 'USD')),
            ]),

            Grid::make(2)->schema([
                Forms\Components\DatePicker::make('issue_date')->label('Issue date'),
                Forms\Components\DatePicker::make('delivery_date')->label('Delivery date'),
            ]),

            Grid::make(2)->schema([
                Forms\Components\Select::make('delivery_type')
                    ->options(['collect' => 'Collect', 'deliver' => 'Deliver'])
                    ->live(),
                Forms\Components\Textarea::make('delivery_instructions')
                    ->rows(2),
            ]),

            Forms\Components\Repeater::make('products')
                ->label('Line items')
                ->schema([
                    Forms\Components\Hidden::make('purchase_order_line_id'),
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

            Grid::make(3)->schema([
                Forms\Components\TextInput::make('sub_total')
                    ->label('Subtotal')
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchase_order_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('total')
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivery_date')
                    ->date()
                    ->sortable()
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
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => ViewPurchaseOrder::route('/{record}'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
