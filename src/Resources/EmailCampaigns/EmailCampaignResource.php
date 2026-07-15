<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns;

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
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;
use VentureDrake\LaravelCrm\Models\EmailTemplate;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\CreateEmailCampaign;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\EditEmailCampaign;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\ListEmailCampaigns;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\ViewEmailCampaign;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\RelationManagers\ClicksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\RelationManagers\RecipientsRelationManager;

class EmailCampaignResource extends Resource
{
    use UsesExternalIdRouting;

    protected static ?string $model = EmailCampaign::class;

    protected static ?string $slug = 'email-campaigns';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 70;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Marketing';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Select::make('email_template_id')
                    ->label(__('laravel-crm-filament::labels.fields.template'))
                    ->options(fn () => EmailTemplate::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $template = EmailTemplate::find($state);
                        if ($template) {
                            $set('subject', $template->subject);
                            $set('preview_text', $template->preview_text);
                            $set('body', $template->body);
                        }
                    }),
            ]),
            Forms\Components\TextInput::make('subject')->maxLength(255)->columnSpanFull(),
            Forms\Components\TextInput::make('preview_text')->maxLength(255)->columnSpanFull(),
            Forms\Components\RichEditor::make('body')->required()->columnSpanFull(),
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
                    TextEntry::make('sent_count_state')
                        ->label(__('laravel-crm-filament::labels.campaign.sent'))
                        ->state(fn (EmailCampaign $record) => EmailCampaignRecipient::where('email_campaign_id', $record->id)->where('status', 'sent')->count())
                        ->numeric(),
                    TextEntry::make('failed_count_state')
                        ->label(__('laravel-crm-filament::labels.campaign.failed'))
                        ->state(fn (EmailCampaign $record) => EmailCampaignRecipient::where('email_campaign_id', $record->id)->whereIn('status', ['failed', 'bounced'])->count())
                        ->numeric(),
                    TextEntry::make('skipped_count_state')
                        ->label(__('laravel-crm-filament::labels.campaign.skipped'))
                        ->state(fn (EmailCampaign $record) => EmailCampaignRecipient::where('email_campaign_id', $record->id)->where('status', 'skipped')->count())
                        ->numeric(),
                    TextEntry::make('open_rate')
                        ->label(__('laravel-crm-filament::labels.campaign.open_rate'))
                        ->state(fn (EmailCampaign $record): string => $record->openRate() . '%'),
                    TextEntry::make('click_rate')
                        ->label(__('laravel-crm-filament::labels.campaign.click_rate'))
                        ->state(fn (EmailCampaign $record): string => $record->clickRate() . '%'),
                    TextEntry::make('unsubscribe_rate')
                        ->label(__('laravel-crm-filament::labels.campaign.unsubscribe_rate'))
                        ->state(fn (EmailCampaign $record): string => $record->unsubscribeRate() . '%'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('subject')->limit(60)->toggleable(),
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
                Tables\Columns\TextColumn::make('unique_opens_count')->label(__('laravel-crm-filament::labels.campaign.opens'))->numeric()->toggleable(),
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
                Actions\EditAction::make()
                    ->visible(fn ($record) => $record->isEditable())
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
            'index' => ListEmailCampaigns::route('/'),
            'create' => CreateEmailCampaign::route('/create'),
            'view' => ViewEmailCampaign::route('/{record}'),
            'edit' => EditEmailCampaign::route('/{record}/edit'),
        ];
    }
}
