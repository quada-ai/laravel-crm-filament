<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Notes;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrmFilament\Concerns\StandaloneActivityResource;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Notes\Pages\ListNotes;
use VentureDrake\LaravelCrmFilament\Resources\Notes\Pages\ViewNote;
use VentureDrake\LaravelCrmFilament\Support\ParentTypeOptions;

class NoteResource extends Resource
{
    use StandaloneActivityResource;

    protected static ?string $model = Note::class;

    protected static ?string $slug = 'notes';

    protected static ?string $recordTitleAttribute = 'content';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?int $navigationSort = 70;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Activity';
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Textarea::make('content')->rows(4)->disabled()->columnSpanFull(),
            Forms\Components\TextInput::make('noteable_type')->label(__('laravel-crm-filament::labels.fields.parent_type'))->disabled(),
            Forms\Components\Toggle::make('pinned')->disabled(),
            Forms\Components\DateTimePicker::make('noted_at')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('content')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('noteable_type')
                    ->label(__('laravel-crm-filament::labels.fields.parent'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (ParentTypeOptions::all()[$state] ?? class_basename($state)) : '—'),

                Tables\Columns\TextColumn::make('createdByUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->toggleable(),

                Tables\Columns\IconColumn::make('pinned')->boolean()->toggleable(),

                Tables\Columns\TextColumn::make('noted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('noteable_type')
                    ->label(__('laravel-crm-filament::labels.fields.parent_type'))
                    ->options(ParentTypeOptions::all()),
                Tables\Filters\SelectFilter::make('user_created_id')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->relationship('createdByUser', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->schema([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                Actions\ViewAction::make()
                    ->button()
                    ->hiddenLabel(),
                static::openParentAction('noteable_type', 'noteable_id'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotes::route('/'),
            'view' => ViewNote::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
