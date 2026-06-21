<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;
use VentureDrake\LaravelCrm\Models\SmsTemplate;
use VentureDrake\LaravelCrm\Services\ClickSendService;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\CreateSmsCampaign;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\EditSmsCampaign;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\ListSmsCampaigns;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\ViewSmsCampaign;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers\ClicksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers\RecipientsRelationManager;

class SmsCampaignResource extends Resource
{
    protected static ?string $model = SmsCampaign::class;

    protected static ?string $slug = 'sms-campaigns';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?int $navigationSort = 71;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Marketing';
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
                    ->label(__('laravel-crm-filament::labels.fields.template'))
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
                ->label(__('laravel-crm-filament::labels.campaign.from'))
                ->default(fn () => app(ClickSendService::class)->defaultFrom())
                ->maxLength(11)
                ->helperText('Sender ID (alphanumeric, ≤11 chars) or leave blank for ClickSend default.'),
            Forms\Components\Textarea::make('body')
                ->required()
                ->rows(6)
                ->maxLength(1530)
                ->live(onBlur: true)
                ->helperText(function (?string $state): string {
                    $count = SmsCampaignMessage::segmentCount($state ?? '');

                    return 'Max 1530 chars (≈10 SMS segments). Current estimate: ' . $count . ' segment(s). Placeholders: {first_name}, {last_name}, {full_name}, {company_name}.';
                })
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label(__('laravel-crm-filament::labels.campaign.schedule_for'))
                ->helperText('Leave blank to keep as draft; use the Schedule action after saving.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Performance')->heading(__('laravel-crm-filament::labels.sections.performance'))
                ->key('campaign_performance')
                ->description('Engagement metrics for this campaign')
                ->columns(3)
                ->schema([
                    TextEntry::make('sent_count')
                        ->label(__('laravel-crm-filament::labels.campaign.sent'))
                        ->state(fn (SmsCampaign $record) => SmsCampaignRecipient::where('sms_campaign_id', $record->id)->whereIn('status', ['sent', 'delivered'])->count())
                        ->numeric(),
                    TextEntry::make('failed_count_state')
                        ->label(__('laravel-crm-filament::labels.campaign.failed'))
                        ->state(fn (SmsCampaign $record) => SmsCampaignRecipient::where('sms_campaign_id', $record->id)->whereIn('status', ['failed', 'bounced'])->count())
                        ->numeric(),
                    TextEntry::make('skipped_count_state')
                        ->label(__('laravel-crm-filament::labels.campaign.skipped'))
                        ->state(fn (SmsCampaign $record) => SmsCampaignRecipient::where('sms_campaign_id', $record->id)->where('status', 'skipped')->count())
                        ->numeric(),
                    TextEntry::make('delivery_rate')
                        ->label(__('laravel-crm-filament::labels.campaign.delivery_rate'))
                        ->state(fn (SmsCampaign $record): string => $record->deliveryRate() . '%'),
                    TextEntry::make('click_rate')
                        ->label(__('laravel-crm-filament::labels.campaign.click_rate'))
                        ->state(fn (SmsCampaign $record): string => $record->clickRate() . '%'),
                    TextEntry::make('unsubscribe_rate')
                        ->label(__('laravel-crm-filament::labels.campaign.unsubscribe_rate'))
                        ->state(fn (SmsCampaign $record): string => $record->unsubscribeRate() . '%'),
                ]),
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
                Tables\Columns\TextColumn::make('total_recipients')->label(__('laravel-crm-filament::labels.campaign.recipients'))->numeric()->toggleable(),
                Tables\Columns\TextColumn::make('delivered_count')->label(__('laravel-crm-filament::labels.money.delivered'))->numeric()->toggleable(),
                Tables\Columns\TextColumn::make('failed_count')->label(__('laravel-crm-filament::labels.campaign.failed'))->numeric()->toggleable(),
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
                Actions\ViewAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\EditAction::make()->visible(fn ($record) => $record->isEditable())
                    ->button()
                    ->hiddenLabel(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RecipientsRelationManager::class,
            ClicksRelationManager::class,
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
