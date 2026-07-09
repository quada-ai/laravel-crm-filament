<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Tasks;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages\CreateTask;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages\EditTask;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages\ListTasks;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages\TaskKanban;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages\ViewTask;

class TaskResource extends Resource
{
    use HasCrmCustomFields;

    protected static ?string $model = Task::class;

    protected static ?string $slug = 'tasks';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Activity';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Task::query()->whereNull('completed_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\DateTimePicker::make('due_at')
                ->label(__('laravel-crm-filament::labels.money.due_at')),

            Forms\Components\Select::make('user_owner_id')
                ->label(__('laravel-crm-filament::labels.fields.owner'))
                ->relationship('ownerUser', 'name')
                ->searchable()
                ->preload(),

            Forms\Components\Select::make('user_assigned_id')
                ->label(__('laravel-crm-filament::labels.fields.assigned_to'))
                ->relationship('assignedToUser', 'name')
                ->searchable()
                ->preload(),
        ];

        if ($customFields = static::crmCustomFieldsSection(Task::class)) {
            $components[] = $customFields;
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('completed_at')
                    ->label(__('laravel-crm-filament::labels.money.done'))
                    ->boolean()
                    ->getStateUsing(fn (Task $record): bool => $record->completed_at !== null),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('due_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('assignedToUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.assigned'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('due_at', 'asc')
            ->filters([])
            ->recordActions([
                Actions\ViewAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\EditAction::make()
                    ->button()
                    ->hiddenLabel(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('markComplete')
                        ->label(__('laravel-crm-filament::labels.actions.mark_complete'))
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            foreach ($records as $record) {
                                if (! $record->completed_at) {
                                    $record->update(['completed_at' => now()]);
                                    $updated++;
                                }
                            }
                            Notification::make()->title($updated . ' task(s) marked complete')->success()->send();
                        }),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'kanban' => TaskKanban::route('/kanban'),
            'create' => CreateTask::route('/create'),
            'view' => ViewTask::route('/{record}'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }
}
