<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deliveries;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages\CreateDelivery;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages\EditDelivery;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages\ListDeliveries;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages\ViewDelivery;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static ?string $slug = 'deliveries';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 54;

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
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('delivery_expected')
                    ->label('Expected'),
                Forms\Components\DatePicker::make('delivered_on')
                    ->label('Delivered on'),
            ]),

            // Delivery line items: only order_product_id + quantity per service contract.
            Forms\Components\Repeater::make('products')
                ->label('Items delivered')
                ->schema([
                    Forms\Components\Select::make('order_product_id')
                        ->label('Order line')
                        ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                            $orderId = $get('../../order_id');
                            if (! $orderId) {
                                return [];
                            }
                            return OrderProduct::query()
                                ->where('order_id', $orderId)
                                ->with('product')
                                ->get()
                                ->mapWithKeys(fn ($op) => [
                                    $op->id => ($op->product?->name ?? 'Line '.$op->id).' (qty '.$op->quantity.')',
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->default(1)
                        ->minValue(0),
                ])
                ->columns(2)
                ->addActionLabel('Add line')
                ->defaultItems(0)
                ->columnSpanFull(),

            Forms\Components\Repeater::make('addresses')
                ->label('Delivery addresses')
                ->schema([
                    Forms\Components\Hidden::make('id'),
                    Grid::make(2)->schema([
                        Forms\Components\Select::make('address_type_id')
                            ->label('Type')
                            ->options(fn () => AddressType::query()->pluck('name', 'id')),
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.order_id')
                    ->label('Order')
                    ->searchable(),

                Tables\Columns\TextColumn::make('delivery_expected')
                    ->label('Expected')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivered_on')
                    ->label('Delivered')
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
            'index' => ListDeliveries::route('/'),
            'create' => CreateDelivery::route('/create'),
            'view' => ViewDelivery::route('/{record}'),
            'edit' => EditDelivery::route('/{record}/edit'),
        ];
    }
}
