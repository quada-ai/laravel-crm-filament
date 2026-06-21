<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Orders;

use BackedEnum;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\LeadDealContactSection;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\LineItemsRepeater;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\MoneyTotalsRow;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\OrderAddressTabs;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\SalesDetailsSection;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFieldEntries;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\Concerns\HasXeroSyncStateInfolist;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmActivitiesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmCallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmFilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmLunchesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmMeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmNotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmTasksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\CreateOrder;
use VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\EditOrder;
use VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\ListOrders;
use VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\ViewOrder;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

class OrderResource extends Resource
{
    use HasCrmCustomFieldEntries;
    use HasCrmCustomFields;
    use HasLabels;
    use HasPrimaryBulkActions;
    use HasXeroSyncStateInfolist;
    use UsesExternalIdRouting;

    protected static ?string $model = Order::class;

    protected static ?string $slug = 'orders';

    protected static ?string $recordTitleAttribute = 'order_id';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 51;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Sales';
    }

    public static function form(Schema $schema): Schema
    {
        $details = SalesDetailsSection::make([
            'title' => false,
            'description' => true,
            'reference' => true,
            'currency' => true,
            'issueDateKey' => null,
            'expiryDateKey' => null,
            'terms' => false,
            'stage' => false,
            'owner' => true,
            'labels' => true,
            'labelsField' => fn () => static::labelsField(),
            'customFields' => static::crmCustomFieldsSection(Order::class),
        ]);

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->columnSpanFull()->schema([
                Grid::make(1)
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        $details,
                        OrderAddressTabs::make(),
                    ]),

                Section::make(__('laravel-crm-filament::labels.sections.products'))
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        LineItemsRepeater::products('order_product_id', 'unit_price')->defaultItems(1),
                        MoneyTotalsRow::make(),
                    ]),
            ]),
        ]);
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

                Tables\Columns\TextColumn::make('order_id')
                    ->label(__('laravel-crm-filament::labels.fields.number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label(__('laravel-crm-filament::labels.fields.reference'))
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('quote.quote_id')
                    ->label(__('laravel-crm-filament::labels.money.quote'))
                    ->url(fn ($record) => $record->quote
                        ? QuoteResource::getUrl('view', ['record' => $record->quote])
                        : null)
                    ->color('primary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('labels.name')
                    ->label(__('laravel-crm-filament::labels.fields.labels'))
                    ->badge()
                    ->limitList(3),

                Tables\Columns\TextColumn::make('person.name')
                    ->label(__('laravel-crm-filament::labels.fields.contact'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label(__('laravel-crm-filament::labels.fields.organization'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label(__('laravel-crm-filament::labels.money.subtotal'))
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tax')
                    ->label(__('laravel-crm-filament::labels.money.tax'))
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total')
                    ->label(__('laravel-crm-filament::labels.money.total'))
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->placeholder(__('laravel-crm-filament::labels.misc.unallocated'))
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
                    ->button()
                    ->hiddenLabel(),
                Actions\EditAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\DeleteAction::make()
                    ->button()
                    ->requiresConfirmation()
                    ->hiddenLabel(),
            ])
            ->toolbarActions([
                static::primaryBulkActionGroup(),
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['order_id', 'reference'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) ($record->order_id ?? $record->getKey());
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter(['Reference' => $record->reference]);
    }

    public static function getRelations(): array
    {
        return [
            CrmActivitiesRelationManager::class,
            CrmNotesRelationManager::class,
            CrmTasksRelationManager::class,
            CrmCallsRelationManager::class,
            CrmMeetingsRelationManager::class,
            CrmLunchesRelationManager::class,
            CrmFilesRelationManager::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('laravel-crm-filament::labels.sections.details'))
                ->schema(fn (?Order $record) => array_merge([
                    TextEntry::make('created_at')
                        ->label(__('laravel-crm-filament::labels.fields.created'))
                        ->since(),

                    TextEntry::make('order_id')
                        ->label(__('laravel-crm-filament::labels.fields.number')),

                    TextEntry::make('reference')
                        ->label(__('laravel-crm-filament::labels.fields.reference')),

                    TextEntry::make('description')
                        ->label(__('laravel-crm-filament::labels.fields.description'))
                        ->columnSpanFull(),

                    TextEntry::make('quote.quote_id')
                        ->label(__('laravel-crm-filament::labels.money.quote'))
                        ->url(fn ($record) => $record?->quote
                            ? QuoteResource::getUrl('view', ['record' => $record->quote])
                            : null),

                    TextEntry::make('subtotal')
                        ->label(__('laravel-crm-filament::labels.money.subtotal'))
                        ->money(fn ($record) => $record?->currency ?: config('laravel-crm.default_currency', 'USD')),

                    TextEntry::make('tax')
                        ->label(__('laravel-crm-filament::labels.money.tax'))
                        ->money(fn ($record) => $record?->currency ?: config('laravel-crm.default_currency', 'USD')),

                    TextEntry::make('total')
                        ->label(__('laravel-crm-filament::labels.money.total'))
                        ->money(fn ($record) => $record?->currency ?: config('laravel-crm.default_currency', 'USD')),

                    TextEntry::make('labels.name')
                        ->label(__('laravel-crm-filament::labels.fields.labels'))
                        ->badge(),

                    TextEntry::make('ownerUser.name')
                        ->label(__('laravel-crm-filament::labels.fields.owner'))
                        ->placeholder(__('laravel-crm-filament::labels.misc.unallocated')),
                ], $record ? static::crmCustomFieldEntries($record, false) : [])),

            Section::make(__('laravel-crm-filament::labels.sections.contact'))
                ->schema([
                    TextEntry::make('person.name')
                        ->label(__('laravel-crm-filament::labels.fields.contact'))
                        ->state(fn ($record) => LeadDealContactSection::personLabel($record?->person))
                        ->url(fn ($record) => $record?->person
                            ? PersonResource::getUrl('view', ['record' => $record->person])
                            : null),

                    TextEntry::make('organization.name')
                        ->label(__('laravel-crm-filament::labels.fields.organization'))
                        ->url(fn ($record) => $record?->organization
                            ? OrganizationResource::getUrl('view', ['record' => $record->organization])
                            : null),
                ]),

            Section::make(__('laravel-crm-filament::labels.sections.custom_fields'))
                ->schema(fn (?Order $record) => $record ? static::crmCustomFieldEntries($record, true) : [])
                ->hidden(function ($record): bool {
                    if (! $record instanceof Order) {
                        return true;
                    }

                    return ! $record->fields()
                        ->whereHas('field', fn ($q) => $q->whereNotNull('field_group_id'))
                        ->exists();
                }),

            static::xeroSyncStateSection(function (Order $order) {
                $invoice = $order->invoices()->latest()->first();

                return $invoice?->xeroInvoice;
            }),
        ])->columns(1);
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
