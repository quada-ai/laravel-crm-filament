<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\SmsTemplates;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\SmsTemplate;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\SmsTemplates\Pages\CreateSmsTemplate;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\SmsTemplates\Pages\EditSmsTemplate;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\SmsTemplates\Pages\ListSmsTemplates;

class SmsTemplateResource extends Resource
{
    protected static ?string $model = SmsTemplate::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'sms-templates';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('body')
                ->required()
                ->rows(6)
                ->maxLength(1530)
                ->helperText('Max 1530 characters (≈10 SMS segments).')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('body')->limit(80)->wrap()->toggleable(),
                Tables\Columns\IconColumn::make('is_system')->label('System')->boolean(),
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
            'index' => ListSmsTemplates::route('/'),
            'create' => CreateSmsTemplate::route('/create'),
            'edit' => EditSmsTemplate::route('/{record}/edit'),
        ];
    }
}
