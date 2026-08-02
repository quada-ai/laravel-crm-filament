<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Meetings;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrmFilament\Concerns\StandaloneActivityResource;
use VentureDrake\LaravelCrmFilament\Concerns\TranslatableResource;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Meetings\Pages\ListMeetings;
use VentureDrake\LaravelCrmFilament\Resources\Meetings\Pages\ViewMeeting;
use VentureDrake\LaravelCrmFilament\Support\ParentTypeOptions;

class MeetingResource extends Resource
{
    use StandaloneActivityResource;
    use TranslatableResource;

    protected static string $resourceTranslationKey = 'meeting';
    protected static string $navigationGroupKey = 'activity';

    protected static ?string $model = Meeting::class;

    protected static ?string $slug = 'meetings';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 72;

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\Textarea::make('description')->rows(3)->disabled()->columnSpanFull(),
            Forms\Components\TextInput::make('meetingable_type')->label(__('laravel-crm-filament::labels.fields.parent_type'))->disabled(),
            Forms\Components\DateTimePicker::make('start_at')->disabled(),
            Forms\Components\DateTimePicker::make('finish_at')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('meetingable_type')
                    ->label(__('laravel-crm-filament::labels.fields.parent'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (ParentTypeOptions::all()[$state] ?? class_basename($state)) : '—'),
                Tables\Columns\TextColumn::make('ownerUser.name')->label(__('laravel-crm-filament::labels.fields.owner'))->toggleable(),
                Tables\Columns\TextColumn::make('start_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('finish_at')->dateTime()->toggleable(),
            ])
            ->defaultSort('start_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('meetingable_type')
                    ->label(__('laravel-crm-filament::labels.fields.parent_type'))
                    ->options(ParentTypeOptions::all()),
                Tables\Filters\SelectFilter::make('user_owner_id')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->relationship('ownerUser', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('start_at')
                    ->schema([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('start_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('start_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                Actions\ViewAction::make()
                    ->button()
                    ->hiddenLabel(),
                static::openParentAction('meetingable_type', 'meetingable_id'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetings::route('/'),
            'view' => ViewMeeting::route('/{record}'),
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
