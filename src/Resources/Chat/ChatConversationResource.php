<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Chat;

use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Services\ChatService;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Chat\Pages\ListChatConversations;
use VentureDrake\LaravelCrmFilament\Resources\Chat\Pages\ViewChatConversation;

class ChatConversationResource extends Resource
{
    protected static ?string $model = ChatConversation::class;

    protected static ?string $slug = 'chat';

    protected static ?string $recordTitleAttribute = 'chat_id';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 80;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup();
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Conversations';
    }

    public static function getModelLabel(): string
    {
        return 'Conversation';
    }

    public static function form(Schema $schema): Schema
    {
        // Conversations are visitor-initiated; this form is unused but Filament expects it.
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('chat_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('visitor.name')
                    ->label('Visitor')
                    ->placeholder('Anonymous')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')->limit(40)->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'open' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('assignedToUser.name')
                    ->label('Assigned')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('Last activity')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['open' => 'Open', 'closed' => 'Closed']),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ChatConversation $record) => $record->status === 'open')
                    ->action(function (ChatConversation $record): void {
                        app(ChatService::class)->close($record);
                        Notification::make()->title('Conversation closed')->success()->send();
                    }),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('close')
                        ->label('Bulk close')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $service = app(\VentureDrake\LaravelCrm\Services\ChatService::class);
                            foreach ($records as $record) {
                                if ($record->status === 'open') {
                                    $service->close($record);
                                }
                            }
                            \Filament\Notifications\Notification::make()->title($records->count().' conversation(s) closed')->success()->send();
                        }),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChatConversations::route('/'),
            'view' => ViewChatConversation::route('/{record}'),
        ];
    }
}
