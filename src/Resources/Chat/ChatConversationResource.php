<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Chat;

use BackedEnum;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
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

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 80;

    /**
     * Status enum mirror for the conversations table. Matches the core
     * migration's enum: open | pending | closed. "pending" is the
     * "replied / waiting on visitor" state the inbox uses.
     */
    public const STATUSES = [
        'open' => 'Open',
        'pending' => 'Replied (waiting on visitor)',
        'closed' => 'Closed',
    ];

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Activity';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ChatConversation::query()->where('status', 'open')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
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
                    ->label(__('laravel-crm-filament::labels.fields.id'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('visitor.name')
                    ->label(__('laravel-crm-filament::labels.chat.visitor'))
                    ->placeholder('Anonymous')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')->limit(40)->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'pending' => 'warning',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assignedToUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.assigned'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_message_at')
                    ->label(__('laravel-crm-filament::labels.fields.last_activity'))
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(self::STATUSES),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\Action::make('markReplied')
                    ->label(__('laravel-crm-filament::labels.actions.mark_replied'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ChatConversation $record) => $record->status === 'open')
                    ->action(function (ChatConversation $record): void {
                        app(ChatService::class)->markRead($record, 'visitor');
                        $record->update(['status' => 'pending']);
                        Notification::make()->title('Marked as replied')->success()->send();
                    }),
                Actions\Action::make('close')
                    ->label(__('laravel-crm-filament::labels.actions.close'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ChatConversation $record) => $record->status !== 'closed')
                    ->action(function (ChatConversation $record): void {
                        app(ChatService::class)->close($record);
                        Notification::make()->title('Conversation closed')->success()->send();
                    }),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('markReplied')
                        ->label(__('laravel-crm-filament::labels.actions.bulk_mark_replied'))
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $service = app(ChatService::class);
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'open') {
                                    $service->markRead($record, 'visitor');
                                    $record->update(['status' => 'pending']);
                                    $count++;
                                }
                            }
                            Notification::make()->title($count . ' conversation(s) marked replied')->success()->send();
                        }),
                    Actions\BulkAction::make('close')
                        ->label(__('laravel-crm-filament::labels.actions.bulk_close'))
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $service = app(ChatService::class);
                            foreach ($records as $record) {
                                if ($record->status !== 'closed') {
                                    $service->close($record);
                                }
                            }
                            Notification::make()->title($records->count() . ' conversation(s) closed')->success()->send();
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
