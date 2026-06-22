<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Teams\Pages;

use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use VentureDrake\LaravelCrmFilament\Resources\Teams\CrmTeamResource;
use VentureDrake\LaravelCrmFilament\Resources\Users\UserResource;

class ViewCrmTeam extends ViewRecord
{
    protected static string $resource = CrmTeamResource::class;

    public function getTitle(): string | Htmlable
    {
        return $this->getRecordTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            CrmTeamResource::backToIndexAction(),
            Actions\EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->hiddenLabel()
                ->tooltip('Edit'),
            Actions\DeleteAction::make()
                ->icon('heroicon-m-trash')
                ->hiddenLabel()
                ->tooltip('Delete'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Group::make([
                    $this->getInfolistContentComponent(),
                ])->columnSpan(['lg' => 1]),
                Group::make([
                    $this->buildUsersSection(),
                ])->columnSpan(['lg' => 1]),
            ]),
        ]);
    }

    protected function buildUsersSection(): Section
    {
        $record = $this->getRecord();
        $users = collect();

        if ($record && method_exists($record, 'users')) {
            $users = $record->users()->orderBy('name')->get();
        }

        $heading = __('laravel-crm-filament::labels.misc.users') . ' (' . $users->count() . ')';

        return Section::make($heading)
            ->schema(
                $users->isEmpty()
                    ? [
                        TextEntry::make('no_users')
                            ->hiddenLabel()
                            ->state(__('laravel-crm-filament::labels.misc.no_users')),
                    ]
                    : $users->map(fn ($user) => TextEntry::make('user_' . $user->id)
                        ->hiddenLabel()
                        ->icon('heroicon-m-user')
                        ->state($user->name)
                        ->color('primary')
                        ->url(UserResource::getUrl('view', ['record' => $user])))
                        ->all()
            );
    }
}
