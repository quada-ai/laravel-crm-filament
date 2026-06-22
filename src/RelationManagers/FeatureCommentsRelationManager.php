<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\FeatureComment;
use VentureDrake\LaravelCrm\Services\FeatureService;

class FeatureCommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Textarea::make('body')
                ->label(__('laravel-crm-filament::labels.fields.message'))
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\Select::make('parent_id')
                ->label(__('laravel-crm-filament::labels.fields.parent'))
                ->options(fn (RelationManager $livewire) => $livewire->getOwnerRecord()
                    ->comments()
                    ->orderBy('created_at', 'desc')
                    ->limit(100)
                    ->get()
                    ->mapWithKeys(fn (FeatureComment $c) => [
                        $c->id => str(strip_tags($c->body))->limit(60)->toString(),
                    ])
                    ->all())
                ->searchable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                Tables\Columns\TextColumn::make('createdByUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.by'))
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('body')
                    ->label(__('laravel-crm-filament::labels.fields.message'))
                    ->limit(80)
                    ->tooltip(fn (FeatureComment $record): ?string => $record->body)
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_admin_reply')
                    ->label(__('laravel-crm-filament::labels.fields.admin_reply'))
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.timestamp'))
                    ->dateTime()
                    ->since()
                    ->tooltip(fn (FeatureComment $record): ?string => optional($record->created_at)->toDateTimeString())
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Actions\CreateAction::make()
                    ->authorize(fn () => true)
                    ->schema([
                        Forms\Components\Textarea::make('body')
                            ->label(__('laravel-crm-filament::labels.fields.message'))
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('parent_id')
                            ->label(__('laravel-crm-filament::labels.fields.parent'))
                            ->options(fn (RelationManager $livewire) => $livewire->getOwnerRecord()
                                ->comments()
                                ->orderBy('created_at', 'desc')
                                ->limit(100)
                                ->get()
                                ->mapWithKeys(fn (FeatureComment $c) => [
                                    $c->id => str(strip_tags($c->body))->limit(60)->toString(),
                                ])
                                ->all())
                            ->searchable(),
                    ])
                    ->using(function (array $data, RelationManager $livewire): FeatureComment {
                        $feature = $livewire->getOwnerRecord();

                        $comment = app(FeatureService::class)->comment(
                            $feature,
                            auth()->user(),
                            $data['body']
                        );

                        if (! empty($data['parent_id'])) {
                            $comment->forceFill(['parent_id' => $data['parent_id']])->save();
                        }

                        return $comment;
                    })
                    ->after(function () {
                        Notification::make()
                            ->title('Comment added')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Actions\EditAction::make()
                    ->authorize(fn () => true),
                Actions\DeleteAction::make()
                    ->authorize(fn () => true)
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([]);
    }
}
