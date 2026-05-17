<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Tasks;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages\CreateTask;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages\EditTask;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages\ListTasks;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages\ViewTask;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $slug = 'tasks';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?int $navigationSort = 60;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup();
    }

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

            Forms\Components\Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\DateTimePicker::make('due_at')
                ->label('Due at'),

            Forms\Components\Select::make('user_owner_id')
                ->label('Owner')
                ->relationship('userOwner', 'name')
                ->searchable()
                ->preload(),

            Forms\Components\Select::make('user_assigned_id')
                ->label('Assigned to')
                ->relationship('userAssigned', 'name')
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('completed_at')
                    ->label('Done')
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

                Tables\Columns\TextColumn::make('userAssigned.name')
                    ->label('Assigned')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('userOwner.name')
                    ->label('Owner')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('due_at', 'asc')
            ->filters([
                Tables\Filters\Filter::make('open')
                    ->label('Open only')
                    ->query(fn ($query) => $query->whereNull('completed_at'))
                    ->default(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
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
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'view' => ViewTask::route('/{record}'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }
}

