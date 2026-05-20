<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ChatWidgets;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\ChatWidget;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ChatWidgets\Pages\CreateChatWidget;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ChatWidgets\Pages\EditChatWidget;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ChatWidgets\Pages\ListChatWidgets;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ChatWidgets\Pages\ViewChatWidget;

class ChatWidgetResource extends Resource
{
    protected static ?string $model = ChatWidget::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'chat-widgets';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-window';

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
            ]),
            Forms\Components\TagsInput::make('allowed_origins')
                ->label('Allowed origins')
                ->placeholder('https://example.com')
                ->helperText('Hostnames permitted to embed this widget; leave empty to allow all.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\TextColumn::make('public_key')
                    ->label('Public key')
                    ->copyable()
                    ->limit(20)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('conversations_count')
                    ->counts('conversations')
                    ->label('Conversations')
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChatWidgets::route('/'),
            'create' => CreateChatWidget::route('/create'),
            'view' => ViewChatWidget::route('/{record}'),
            'edit' => EditChatWidget::route('/{record}/edit'),
        ];
    }
}
