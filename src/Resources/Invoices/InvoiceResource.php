<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\ExportsCsv;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\CreateInvoice;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\EditInvoice;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\ListInvoices;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\ViewInvoice;

class InvoiceResource extends Resource
{
    use HasCrmCustomFields;

    protected static ?string $model = Invoice::class;

    protected static ?string $slug = 'invoices';

    protected static ?string $recordTitleAttribute = 'invoice_id';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?int $navigationSort = 52;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Sales';
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
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
                Forms\Components\DatePicker::make('due_date')->label('Due date'),
            ]),

            Forms\Components\Repeater::make('products')
                ->label('Line items')
                ->schema([
                    Forms\Components\Hidden::make('invoice_line_id'),
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
        ];

        if ($customFields = static::crmCustomFieldsSection(Invoice::class)) {
            $components[] = $customFields;
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_id')
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

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
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
                    ExportsCsv::action(
                        columns: [
                            'ID' => fn ($r) => $r->invoice_id,
                            'Reference' => fn ($r) => $r->reference,
                            'Issue date' => fn ($r) => $r->issue_date,
                            'Due date' => fn ($r) => $r->due_date,
                            'Total' => fn ($r) => ($r->total ?? 0) / 100,
                            'Currency' => fn ($r) => $r->currency,
                            'Owner' => fn ($r) => optional($r->ownerUser)->name,
                        ],
                        filename: 'invoices',
                    ),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_id', 'reference'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return (string) ($record->invoice_id ?? $record->getKey());
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter(['Reference' => $record->reference]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
