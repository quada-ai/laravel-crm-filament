<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailTemplates;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\EmailTemplate;
use VentureDrake\LaravelCrmFilament\Resources\EmailTemplates\Pages\CreateEmailTemplate;
use VentureDrake\LaravelCrmFilament\Resources\EmailTemplates\Pages\EditEmailTemplate;
use VentureDrake\LaravelCrmFilament\Resources\EmailTemplates\Pages\ListEmailTemplates;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $slug = 'email-templates';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-envelope';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 200;

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('subject')->maxLength(255),
            ]),
            Forms\Components\TextInput::make('preview_text')->maxLength(255)->columnSpanFull(),
            Forms\Components\RichEditor::make('body')
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('subject')->limit(60)->toggleable(),
                Tables\Columns\IconColumn::make('is_system')
                    ->label(__('laravel-crm-filament::labels.fields.system'))
                    ->boolean(),
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
            'index' => ListEmailTemplates::route('/'),
            'create' => CreateEmailTemplate::route('/create'),
            'edit' => EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
