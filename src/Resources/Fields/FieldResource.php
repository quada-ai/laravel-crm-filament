<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Fields;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\Fields\Pages\CreateField;
use VentureDrake\LaravelCrmFilament\Resources\Fields\Pages\EditField;
use VentureDrake\LaravelCrmFilament\Resources\Fields\Pages\ListFields;
use VentureDrake\LaravelCrmFilament\Resources\Fields\Pages\ViewField;

class FieldResource extends Resource
{
    use UsesExternalIdRouting;

    public const TYPES = [
        'text' => 'Text',
        'textarea' => 'Textarea',
        'date' => 'Date',
        'checkbox' => 'Checkbox',
        'select' => 'Select (single)',
        'select_multiple' => 'Select (multiple)',
        'radio' => 'Radio',
        'checkbox_multiple' => 'Checkbox list',
    ];

    public const MODELS = [
        Lead::class => 'Lead',
        Deal::class => 'Deal',
        Person::class => 'Person',
        Organization::class => 'Organization',
        Task::class => 'Task',
        Quote::class => 'Quote',
        Order::class => 'Order',
        Invoice::class => 'Invoice',
        PurchaseOrder::class => 'Purchase Order',
        Product::class => 'Product',
    ];

    protected static ?string $model = Field::class;

    protected static ?string $slug = 'fields';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'Custom Fields';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Grid::make(3)->schema([
                Forms\Components\Select::make('type')
                    ->options(self::TYPES)
                    ->required()
                    ->live(),
                Forms\Components\Select::make('field_group_id')
                    ->label(__('laravel-crm-filament::labels.fields.group'))
                    ->options(fn () => FieldGroup::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
                Forms\Components\TextInput::make('default')->maxLength(255),
            ])->columnSpanFull(),

            Forms\Components\Toggle::make('required')->columnSpanFull(),

            Forms\Components\Repeater::make('options')
                ->label(__('laravel-crm-filament::labels.fields.options'))
                ->schema([
                    Forms\Components\TextInput::make('label')->required(),
                    Forms\Components\TextInput::make('order')->numeric()->default(0),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->addActionLabel('Add option')
                ->visible(fn (Get $get) => in_array($get('type'), ['select', 'select_multiple', 'radio', 'checkbox_multiple'], true))
                ->columnSpanFull(),

            Forms\Components\Select::make('models')
                ->label(__('laravel-crm-filament::labels.fields.available_on'))
                ->multiple()
                ->options(self::MODELS)
                ->searchable()
                ->helperText('Choose which entities this custom field applies to.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('fieldModels'))
            ->columns([
                Tables\Columns\TextColumn::make('type')->badge()->sortable(),
                Tables\Columns\TextColumn::make('fieldGroup.name')
                    ->label(__('laravel-crm-filament::labels.fields.group'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('required')
                    ->label(__('laravel-crm-filament::labels.fields.required'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('default')
                    ->label(__('laravel-crm-filament::labels.fields.default'))
                    ->toggleable(),
                Tables\Columns\IconColumn::make('system')
                    ->label(__('laravel-crm-filament::labels.fields.system'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('attached_to')
                    ->label(__('laravel-crm-filament::labels.fields.attached_to'))
                    ->badge()
                    ->color('gray')
                    ->state(fn ($record) => $record->fieldModels
                        ->map(fn ($fm) => Str::plural(class_basename($fm->model)))
                        ->all()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Actions\ViewAction::make()->button()->hiddenLabel(),
                Actions\EditAction::make()->button()->hiddenLabel(),
                Actions\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function backToIndexAction(): Actions\Action
    {
        return Actions\Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_fields'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFields::route('/'),
            'create' => CreateField::route('/create'),
            'view' => ViewField::route('/{record}'),
            'edit' => EditField::route('/{record}/edit'),
        ];
    }
}
