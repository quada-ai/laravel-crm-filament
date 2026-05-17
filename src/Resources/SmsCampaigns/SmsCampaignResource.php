<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsTemplate;
use VentureDrake\LaravelCrm\Services\ClickSendService;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers\RecipientsRelationManager;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\CreateSmsCampaign;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\EditSmsCampaign;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\ListSmsCampaigns;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\ViewSmsCampaign;

class SmsCampaignResource extends Resource
{
    protected static ?string $model = SmsCampaign::class;

    protected static ?string $slug = 'sms-campaigns';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?int $navigationSort = 71;

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
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Select::make('sms_template_id')
                    ->label('Template')
                    ->options(fn () => SmsTemplate::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $template = SmsTemplate::find($state);
                        if ($template) {
                            $set('body', $template->body);
                        }
                    }),
            ]),
            Forms\Components\TextInput::make('from')
                ->label('From')
                ->default(fn () => app(ClickSendService::class)->defaultFrom())
                ->maxLength(11)
                ->helperText('Sender ID (alphanumeric, ≤11 chars) or leave blank for ClickSend default.'),
            Forms\Components\Textarea::make('body')
                ->required()
                ->rows(6)
                ->maxLength(1530)
                ->helperText('Max 1530 chars (≈10 SMS segments). Placeholders: {first_name}, {last_name}, {full_name}, {company_name}.')
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('Schedule for')
                ->helperText('Leave blank to keep as draft; use the Schedule action after saving.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'scheduled' => 'warning',
                        'sending' => 'info',
                        'sent' => 'success',
                        'cancelled' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_recipients')->label('Recipients')->numeric()->toggleable(),
                Tables\Columns\TextColumn::make('delivered_count')->label('Delivered')->numeric()->toggleable(),
                Tables\Columns\TextColumn::make('failed_count')->label('Failed')->numeric()->toggleable(),
                Tables\Columns\TextColumn::make('scheduled_at')->dateTime()->toggleable(),
                Tables\Columns\TextColumn::make('sent_at')->dateTime()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'sending' => 'Sending',
                        'sent' => 'Sent',
                        'cancelled' => 'Cancelled',
                        'failed' => 'Failed',
                    ]),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make()->visible(fn ($record) => $record->isEditable()),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

public static function getRelations(): array
    {
        return [
            RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsCampaigns::route('/'),
            'create' => CreateSmsCampaign::route('/create'),
            'view' => ViewSmsCampaign::route('/{record}'),
            'edit' => EditSmsCampaign::route('/{record}/edit'),
        ];
    }
}
