<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ChatWidgets;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\ChatWidget;
use VentureDrake\LaravelCrmFilament\Concerns\TranslatableResource;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages\CreateChatWidget;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages\EditChatWidget;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages\ListChatWidgets;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages\ViewChatWidget;

class ChatWidgetResource extends Resource
{
    use TranslatableResource;
    use UsesExternalIdRouting;

    protected static string $resourceTranslationKey = 'chat_widget';
    protected static string $navigationGroupKey = 'settings';

    protected static ?string $model = ChatWidget::class;

    protected static ?string $slug = 'chat-widgets';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-window';

    protected static ?int $navigationSort = 120;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('welcome_message')
                ->label(__('laravel-crm-filament::labels.chat.welcome_message'))
                ->maxLength(255),
            Forms\Components\ColorPicker::make('color')
                ->label(__('laravel-crm-filament::labels.fields.color'))
                ->default('#2563eb'),
            Forms\Components\Select::make('position')
                ->label(__('laravel-crm-filament::labels.chat.position'))
                ->options([
                    'bottom-right' => 'Bottom right',
                    'bottom-left' => 'Bottom left',
                ])
                ->default('bottom-right'),
            Forms\Components\Toggle::make('is_active')
                ->label(__('laravel-crm-filament::labels.fields.active'))
                ->default(true),
            Forms\Components\TagsInput::make('allowed_origins')
                ->label(__('laravel-crm-filament::labels.chat.allowed_origins'))
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
                Tables\Columns\TextColumn::make('public_key')
                    ->label(__('laravel-crm-filament::labels.chat.public_key'))
                    ->copyable()
                    ->limit(20)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label(__('laravel-crm-filament::labels.fields.active'))->boolean(),
                Tables\Columns\TextColumn::make('conversations_count')
                    ->counts('conversations')
                    ->label(__('laravel-crm-filament::labels.chat.conversations'))
                    ->toggleable(),
            ])
            ->defaultSort('name')
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
            ->label(__('laravel-crm-filament::labels.actions.back_to_chat_widgets'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
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
