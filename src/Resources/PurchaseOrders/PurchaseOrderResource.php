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
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\AuditsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\FilesRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;

class PurchaseOrderResource extends Resource
{
    use HasCrmCustomFields;
    use HasLabels;
    use HasPrimaryBulkActions;
    use UsesExternalIdRouting;

    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $slug = 'purchase-orders';

    protected static ?string $recordTitleAttribute = 'purchase_order_id';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 53;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Sales';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
            Grid::make(3)->schema([
                Forms\Components\Select::make('order_id')
                    ->label(__('laravel-crm-filament::labels.fields.order'))
                    ->options(fn () => Order::query()->orderByDesc('id')->limit(50)->get()->mapWithKeys(fn ($o) => [$o->id => $o->order_id])->all())
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('reference')->maxLength(100),
                Forms\Components\TextInput::make('currency')
                    ->maxLength(3)
                    ->default(config('laravel-crm.default_currency', 'USD')),
            ]),

            Grid::make(2)->schema([
                Forms\Components\DatePicker::make('issue_date')->label(__('laravel-crm-filament::labels.money.issue_date')),
                Forms\Components\DatePicker::make('delivery_date')->label(__('laravel-crm-filament::labels.money.delivery_date')),
            ]),

            Grid::make(2)->schema([
                Forms\Components\Select::make('delivery_type')
                    ->label(__('laravel-crm-filament::labels.sales.delivery_type'))
                    ->options(['collect' => 'Collect', 'deliver' => 'Deliver'])
                    ->live(),
                Forms\Components\Textarea::make('delivery_instructions')
                    ->rows(2),
            ]),

            Forms\Components\Repeater::make('products')
                ->label(__('laravel-crm-filament::labels.money.line_items'))
                ->schema([
                    Forms\Components\Hidden::make('purchase_order_line_id'),
                    Forms\Components\Hidden::make('order_product_id'),
                    Forms\Components\Select::make('id')
                        ->label(__('laravel-crm-filament::labels.money.product'))
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
                        ->label(__('laravel-crm-filament::labels.money.unit_price'))
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
                    ->label(__('laravel-crm-filament::labels.money.subtotal'))
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
                ->label(__('laravel-crm-filament::labels.fields.owner'))
                ->relationship('ownerUser', 'name')
                ->searchable()
                ->preload(),

            static::labelsField(),
        ];

        if ($customFields = static::crmCustomFieldsSection(PurchaseOrder::class)) {
            $components[] = $customFields;
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.created'))
                    ->since()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('purchase_order_id')
                    ->label(__('laravel-crm-filament::labels.fields.number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label(__('laravel-crm-filament::labels.fields.reference'))
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('order.order_id')
                    ->label(__('laravel-crm-filament::labels.fields.order'))
                    ->url(fn ($record) => $record->order
                        ? OrderResource::getUrl('view', ['record' => $record->order])
                        : null)
                    ->color('primary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('person.name')
                    ->label(__('laravel-crm-filament::labels.fields.contact'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label(__('laravel-crm-filament::labels.fields.organization'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label(__('laravel-crm-filament::labels.money.issue_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivery_date')
                    ->label(__('laravel-crm-filament::labels.money.delivery_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivery_type')
                    ->label(__('laravel-crm-filament::labels.sales.delivery_type'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total')
                    ->label(__('laravel-crm-filament::labels.money.amount'))
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('sent')
                    ->label(__('laravel-crm-filament::labels.fields.sent'))
                    ->boolean()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_owner_id')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->multiple()
                    ->relationship('ownerUser', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('labels')
                    ->label(__('laravel-crm-filament::labels.fields.labels'))
                    ->multiple()
                    ->relationship('labels', 'name')
                    ->preload(),
            ])
            ->recordActions([
                Actions\ViewAction::make()
                    ->button(),
                Actions\EditAction::make()
                    ->button()
                    ->hidden(fn (?PurchaseOrder $record) => $record !== null && $record->xeroPurchaseOrder()->exists()),
                Actions\DeleteAction::make()
                    ->button()
                    ->requiresConfirmation()
                    ->hidden(fn (?PurchaseOrder $record) => $record !== null && $record->xeroPurchaseOrder()->exists()),
            ])
            ->toolbarActions([
                static::primaryBulkActionGroup(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
            FilesRelationManager::class,
        ];
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
