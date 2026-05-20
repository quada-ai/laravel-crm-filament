<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Fields;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
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
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Fields\Pages\CreateField;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Fields\Pages\EditField;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Fields\Pages\ListFields;

class FieldResource extends Resource
{
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

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'fields';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('handle')->maxLength(255),
            ]),

            Grid::make(2)->schema([
                Forms\Components\Select::make('type')
                    ->options(self::TYPES)
                    ->required()
                    ->live(),
                Forms\Components\Select::make('field_group_id')
                    ->label(__('laravel-crm-filament::labels.fields.group'))
                    ->options(fn () => FieldGroup::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
            ]),

            Grid::make(2)->schema([
                Forms\Components\Toggle::make('required'),
                Forms\Components\TextInput::make('default')->maxLength(255),
            ]),

            Forms\Components\Select::make('models')
                ->label(__('laravel-crm-filament::labels.fields.available_on'))
                ->multiple()
                ->options(self::MODELS)
                ->searchable()
                ->helperText('Choose which entities this custom field applies to.')
                ->columnSpanFull(),

            Forms\Components\Repeater::make('options')
                ->label(__('laravel-crm-filament::labels.fields.options'))
                ->schema([
                    Forms\Components\TextInput::make('label')->required(),
                    Forms\Components\TextInput::make('value'),
                    Forms\Components\TextInput::make('order')->numeric()->default(0),
                ])
                ->columns(3)
                ->defaultItems(0)
                ->addActionLabel('Add option')
                ->visible(fn (Get $get) => in_array($get('type'), ['select', 'select_multiple', 'radio', 'checkbox_multiple'], true))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('fieldGroup.name')->label(__('laravel-crm-filament::labels.fields.group'))->toggleable(),
                Tables\Columns\IconColumn::make('required')->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([Actions\EditAction::make()])
            ->toolbarActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFields::route('/'),
            'create' => CreateField::route('/create'),
            'edit' => EditField::route('/{record}/edit'),
        ];
    }
}
