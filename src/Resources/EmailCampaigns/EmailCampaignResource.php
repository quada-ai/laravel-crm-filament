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
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
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
            Section::make('Details')->heading(__('laravel-crm-filament::labels.sections.details'))
                ->key('campaign_details')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->label(__('laravel-crm-filament::labels.fields.name')),
                    TextEntry::make('campaign_id')
                        ->label(__('laravel-crm-filament::labels.fields.number')),
                    TextEntry::make('subject')
                        ->label(__('laravel-crm-filament::labels.fields.subject'))
                        ->columnSpanFull(),
                    TextEntry::make('preview_text')
                        ->columnSpanFull(),
                    TextEntry::make('status')
                        ->label(__('laravel-crm-filament::labels.fields.status'))
                        ->badge(),
                    TextEntry::make('scheduled_at')
                        ->label(__('laravel-crm-filament::labels.campaign.schedule_for'))
                        ->state(function (EmailCampaign $record): ?string {
                            if (! $record->scheduled_at) {
                                return null;
                            }

                            $formatted = $record->scheduled_at->format('M j, Y g:i A');

                            return $record->timezone
                                ? $formatted . ' (' . $record->timezone . ')'
                                : $formatted;
                        })
                        ->placeholder('—'),
                    TextEntry::make('sent_at')
                        ->dateTime()
                        ->placeholder('—'),
                    TextEntry::make('template.name')
                        ->label(__('laravel-crm-filament::labels.fields.template'))
                        ->placeholder('—'),
                    TextEntry::make('ownerUser.name')
                        ->label(__('laravel-crm-filament::labels.fields.owner'))
                        ->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('campaign_id')
                    ->label(__('laravel-crm-filament::labels.fields.number'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('subject', 'like', "%{$search}%")
                            ->orWhere('campaign_id', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('subject')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->subject)
                    ->toggleable(),
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
                Tables\Columns\TextColumn::make('total_recipients')
                    ->label(__('laravel-crm-filament::labels.campaign.recipients'))
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->since()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->since()
                    ->sortable()
                    ->toggleable(),
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
                Actions\ViewAction::make()->button()->hiddenLabel(),
                Actions\EditAction::make()->visible(fn ($record) => $record->isEditable())->button()->hiddenLabel(),
                Actions\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation(),
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

    public static function backToIndexAction(): Actions\Action
    {
        return Actions\Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_email_campaigns'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
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
