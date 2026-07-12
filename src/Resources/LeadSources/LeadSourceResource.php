<?php

namespace VentureDrake\LaravelCrmFilament\Resources\LeadSources;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\LeadSource;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages\CreateLeadSource;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages\EditLeadSource;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages\ListLeadSources;

class LeadSourceResource extends Resource
{
    use UsesExternalIdRouting;

    protected static ?string $model = LeadSource::class;

    protected static ?string $slug = 'lead-sources';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-link';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 80;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
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
                Tables\Columns\TextColumn::make('description')->limit(60)->toggleable(),
                Tables\Columns\TextColumn::make('leads_count')
                    ->label(__('laravel-crm-filament::labels.sales.leads'))
                    ->counts('leads')
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                Actions\ViewAction::make()->button()->hiddenLabel(),
                Actions\EditAction::make()->button()->hiddenLabel(),
                Actions\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function backToIndexAction(): Actions\Action
    {
        return Actions\Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_lead_sources'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeadSources::route('/'),
            'create' => CreateLeadSource::route('/create'),
            'edit' => EditLeadSource::route('/{record}/edit'),
        ];
    }
}
