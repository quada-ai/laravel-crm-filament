<?php

namespace VentureDrake\LaravelCrmFilament\Resources\LeadStatuses;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\LeadStatus;
use VentureDrake\LaravelCrmFilament\Concerns\TranslatableResource;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\LeadStatuses\Pages\CreateLeadStatus;
use VentureDrake\LaravelCrmFilament\Resources\LeadStatuses\Pages\EditLeadStatus;
use VentureDrake\LaravelCrmFilament\Resources\LeadStatuses\Pages\ListLeadStatuses;

class LeadStatusResource extends Resource
{
    use TranslatableResource;
    use UsesExternalIdRouting;

    protected static string $resourceTranslationKey = 'lead_status';
    protected static string $navigationGroupKey = 'settings';

    protected static ?string $model = LeadStatus::class;

    protected static ?string $slug = 'lead-statuses';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-flag';

    protected static ?int $navigationSort = 85;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]),
            Forms\Components\ColorPicker::make('color'),
            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('order')->sortable(),
                Tables\Columns\ColorColumn::make('color')->toggleable(),
                Tables\Columns\TextColumn::make('description')->limit(60)->toggleable(),
                Tables\Columns\TextColumn::make('leads_count')
                    ->label(__('laravel-crm-filament::labels.sales.leads'))
                    ->counts('leads')
                    ->toggleable(),
            ])
            ->defaultSort('order')
            ->recordActions([
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
            'index' => ListLeadStatuses::route('/'),
            'create' => CreateLeadStatus::route('/create'),
            'edit' => EditLeadStatus::route('/{record}/edit'),
        ];
    }
}
