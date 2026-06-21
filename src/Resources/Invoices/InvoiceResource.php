<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrmFilament\Concerns\ExportsCsv;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\LeadDealContactSection;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\LineItemsRepeater;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\MoneyTotalsRow;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\SalesDetailsSection;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFieldEntries;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\Concerns\HasXeroSyncStateInfolist;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Models\InvoicePayment;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmActivitiesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmCallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmFilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmLunchesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmMeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmNotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmTasksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\CreateInvoice;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\EditInvoice;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\ListInvoices;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\ViewInvoice;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;

class InvoiceResource extends Resource
{
    use HasCrmCustomFieldEntries;
    use HasCrmCustomFields;
    use HasLabels;
    use HasPrimaryBulkActions;
    use HasXeroSyncStateInfolist;
    use UsesExternalIdRouting;

    protected static ?string $model = Invoice::class;

    protected static ?string $slug = 'invoices';

    protected static ?string $recordTitleAttribute = 'invoice_id';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?int $navigationSort = 52;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Sales';
    }

    public static function form(Schema $schema): Schema
    {
        $details = SalesDetailsSection::make([
            'title' => false,
            'description' => false,
            'reference' => true,
            'currency' => true,
            'issueDateKey' => 'issue_date',
            'expiryDateKey' => 'due_date',
            'terms' => true,
            'stage' => false,
            'owner' => true,
            'labels' => true,
            'labelsField' => fn () => static::labelsField(),
            'orderLink' => true,
            'customFields' => static::crmCustomFieldsSection(Invoice::class),
        ]);

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->columnSpanFull()->schema([
                Grid::make(1)
                    ->columnSpan(['lg' => 1])
                    ->schema([$details]),

                Section::make(__('laravel-crm-filament::labels.sections.products'))
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        LineItemsRepeater::products('invoice_line_id', 'unit_price')->defaultItems(1),
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

                Tables\Columns\TextColumn::make('invoice_id')
                    ->label(__('laravel-crm-filament::labels.fields.number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label(__('laravel-crm-filament::labels.fields.reference'))
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('order.order_id')
                    ->label(__('laravel-crm-filament::labels.money.order'))
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

                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('laravel-crm-filament::labels.money.due_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total')
                    ->label(__('laravel-crm-filament::labels.money.total'))
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('fully_paid_at')
                    ->label(__('laravel-crm-filament::labels.xero.fully_paid_at'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label(__('laravel-crm-filament::labels.money.amount_paid'))
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount_due')
                    ->label(__('laravel-crm-filament::labels.money.amount_due'))
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('overdue_by')
                    ->label(__('laravel-crm-filament::labels.money.overdue_by'))
                    ->state(function (Invoice $record): ?string {
                        if ($record->fully_paid_at !== null || $record->due_date === null) {
                            return null;
                        }
                        $due = Carbon::parse($record->due_date);
                        if (! $due->isPast()) {
                            return null;
                        }

                        return $due->diffInDays(now()) . 'd';
                    })
                    ->color('danger')
                    ->toggleable(),

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

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('laravel-crm-filament::labels.fields.status'))
                    ->options([
                        'paid' => __('laravel-crm-filament::labels.money.amount_paid'),
                        'unpaid' => __('laravel-crm-filament::labels.money.amount_due'),
                        'overdue' => __('laravel-crm-filament::labels.money.overdue_by'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if ($value === 'paid') {
                            return $query->whereNotNull('fully_paid_at');
                        }
                        if ($value === 'unpaid') {
                            return $query->whereNull('fully_paid_at');
                        }
                        if ($value === 'overdue') {
                            return $query->whereNull('fully_paid_at')
                                ->whereNotNull('due_date')
                                ->where('due_date', '<', now()->toDateString());
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                Actions\ViewAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\EditAction::make()
                    ->button()
                    ->hidden(fn (Invoice $record): bool => (int) ($record->getAttributes()['amount_paid'] ?? 0) > 0),
                Actions\DeleteAction::make()
                    ->button()
                    ->requiresConfirmation()
                    ->hidden(fn (Invoice $record): bool => (int) ($record->getAttributes()['amount_paid'] ?? 0) > 0),
            ])
            ->toolbarActions([
                static::primaryBulkActionGroup(),
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
                ]),
            ]);
    }

    public static function markPaidAction(): Actions\Action
    {
        return Actions\Action::make('markPaid')
            ->label(__('laravel-crm-filament::labels.actions.mark_paid'))
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->modalHeading(__('laravel-crm-filament::labels.actions.record_payment'))
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label(__('laravel-crm-filament::labels.money.amount'))
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->default(fn (?Invoice $record): float => $record
                        ? round(((int) ($record->getAttributes()['amount_due'] ?? $record->getAttributes()['total'] ?? 0)) / 100, 2)
                        : 0.0),
                Forms\Components\DatePicker::make('paid_at')
                    ->label(__('laravel-crm-filament::labels.money.due_date'))
                    ->required()
                    ->default(now()->toDateString()),
            ])
            ->action(function (Invoice $record, array $data): void {
                $amountDollars = (float) ($data['amount'] ?? 0);
                $paidAt = $data['paid_at'] ?? now()->toDateString();

                $paymentCents = (int) round($amountDollars * 100);
                $currentPaidCents = (int) ($record->getAttributes()['amount_paid'] ?? 0);
                $totalCents = (int) ($record->getAttributes()['total'] ?? 0);
                $newPaidCents = $currentPaidCents + $paymentCents;
                $newDueCents = max(0, $totalCents - $newPaidCents);
                $fullyPaid = $totalCents > 0 && $newPaidCents >= $totalCents;

                InvoicePayment::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'invoice_id' => $record->getKey(),
                    'amount' => $paymentCents,
                    'paid_at' => $paidAt,
                    'user_created_id' => auth()->id(),
                ]);

                // The model setters multiply by 100, so divide first.
                $record->amount_paid = $newPaidCents / 100;
                $record->amount_due = $newDueCents / 100;
                $record->fully_paid_at = $fullyPaid ? $paidAt : null;
                $record->save();

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.mark_paid'))
                    ->success()
                    ->send();
            });
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_id', 'reference'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) ($record->invoice_id ?? $record->getKey());
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
                ->schema(fn (?Invoice $record) => array_merge([
                    TextEntry::make('created_at')
                        ->label(__('laravel-crm-filament::labels.fields.created'))
                        ->since(),

                    TextEntry::make('invoice_id')
                        ->label(__('laravel-crm-filament::labels.fields.number')),

                    TextEntry::make('reference')
                        ->label(__('laravel-crm-filament::labels.fields.reference')),

                    TextEntry::make('order.order_id')
                        ->label(__('laravel-crm-filament::labels.money.order'))
                        ->url(fn ($record) => $record?->order
                            ? OrderResource::getUrl('view', ['record' => $record->order])
                            : null),

                    TextEntry::make('issue_date')
                        ->label(__('laravel-crm-filament::labels.money.issue_date'))
                        ->date(),

                    TextEntry::make('due_date')
                        ->label(__('laravel-crm-filament::labels.money.due_date'))
                        ->date(),

                    TextEntry::make('total')
                        ->label(__('laravel-crm-filament::labels.money.total'))
                        ->money(fn ($record) => $record?->currency ?: config('laravel-crm.default_currency', 'USD')),

                    TextEntry::make('amount_paid')
                        ->label(__('laravel-crm-filament::labels.money.amount_paid'))
                        ->money(fn ($record) => $record?->currency ?: config('laravel-crm.default_currency', 'USD')),

                    TextEntry::make('amount_due')
                        ->label(__('laravel-crm-filament::labels.money.amount_due'))
                        ->money(fn ($record) => $record?->currency ?: config('laravel-crm.default_currency', 'USD')),

                    TextEntry::make('fully_paid_at')
                        ->label(__('laravel-crm-filament::labels.xero.fully_paid_at'))
                        ->date(),

                    TextEntry::make('terms')
                        ->label(__('laravel-crm-filament::labels.fields.terms'))
                        ->columnSpanFull(),

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
                ->schema(fn (?Invoice $record) => $record ? static::crmCustomFieldEntries($record, true) : [])
                ->hidden(function ($record): bool {
                    if (! $record instanceof Invoice) {
                        return true;
                    }

                    return ! $record->fields()
                        ->whereHas('field', fn ($q) => $q->whereNotNull('field_group_id'))
                        ->exists();
                }),

            static::xeroSyncStateSection(fn (Invoice $invoice) => $invoice->xeroInvoice),
        ])->columns(1);
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
