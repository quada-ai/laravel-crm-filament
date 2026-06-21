<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class LeadMeetingsRelationManager extends MeetingsRelationManager
{
    protected string $view = 'laravel-crm-filament::lead-meetings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?int $editingId = null;

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'name' => null,
            'description' => null,
            'start_at' => now(),
            'finish_at' => null,
            'location' => null,
            'user_owner_id' => auth()->id(),
            'user_assigned_id' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Grid::make(2)->schema([
                    Forms\Components\DateTimePicker::make('start_at')
                        ->label(__('laravel-crm-filament::labels.money.start')),
                    Forms\Components\DateTimePicker::make('finish_at')
                        ->label(__('laravel-crm-filament::labels.money.finish')),
                ]),
                Forms\Components\TextInput::make('location')
                    ->label(__('laravel-crm-filament::labels.fields.location'))
                    ->maxLength(255)
                    ->columnSpanFull(),
                Grid::make(2)->schema([
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
                ]),
            ]);
    }

    public function createMeeting(): void
    {
        $data = $this->form->getState();

        $meeting = $this->getOwnerRecord()->meetings()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'start_at' => $data['start_at'] ?? null,
            'finish_at' => $data['finish_at'] ?? null,
            'location' => $data['location'] ?? null,
            'user_owner_id' => $data['user_owner_id'] ?? auth()->id(),
            'user_assigned_id' => $data['user_assigned_id'] ?? null,
            'user_created_id' => auth()->id(),
        ]);

        static::logCrmActivity($this->getOwnerRecord(), $meeting);

        $this->form->fill([
            'name' => null,
            'description' => null,
            'start_at' => now(),
            'finish_at' => null,
            'location' => null,
            'user_owner_id' => auth()->id(),
            'user_assigned_id' => null,
        ]);

        Notification::make()
            ->title('Meeting added')
            ->success()
            ->send();
    }

    public function editMeeting(int $id): void
    {
        $meeting = $this->getOwnerRecord()->meetings()->whereKey($id)->first();

        if ($meeting === null) {
            return;
        }

        $this->editingId = (int) $meeting->id;

        $this->form->fill([
            'name' => $meeting->name,
            'description' => $meeting->description,
            'start_at' => $meeting->start_at,
            'finish_at' => $meeting->finish_at,
            'location' => $meeting->location,
            'user_owner_id' => $meeting->user_owner_id,
            'user_assigned_id' => $meeting->user_assigned_id,
        ]);
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;

        $this->form->fill([
            'name' => null,
            'description' => null,
            'start_at' => now(),
            'finish_at' => null,
            'location' => null,
            'user_owner_id' => auth()->id(),
            'user_assigned_id' => null,
        ]);
    }

    public function updateMeeting(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $data = $this->form->getState();

        $meeting = $this->getOwnerRecord()->meetings()->whereKey($this->editingId)->first();

        if ($meeting === null) {
            $this->cancelEdit();

            return;
        }

        $meeting->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'start_at' => $data['start_at'] ?? null,
            'finish_at' => $data['finish_at'] ?? null,
            'location' => $data['location'] ?? null,
            'user_owner_id' => $data['user_owner_id'] ?? null,
            'user_assigned_id' => $data['user_assigned_id'] ?? null,
            'user_updated_id' => auth()->id(),
        ]);

        static::logCrmActivity($this->getOwnerRecord(), $meeting);

        $this->editingId = null;

        $this->form->fill([
            'name' => null,
            'description' => null,
            'start_at' => now(),
            'finish_at' => null,
            'location' => null,
            'user_owner_id' => auth()->id(),
            'user_assigned_id' => null,
        ]);

        Notification::make()
            ->title('Meeting updated')
            ->success()
            ->send();
    }

    public function deleteMeeting(int $id): void
    {
        $meeting = $this->getOwnerRecord()->meetings()->whereKey($id)->first();

        if ($meeting === null) {
            return;
        }

        $meeting->delete();

        if ($this->editingId === (int) $id) {
            $this->editingId = null;
            $this->form->fill([
                'name' => null,
                'description' => null,
                'start_at' => now(),
                'finish_at' => null,
                'location' => null,
                'user_owner_id' => auth()->id(),
                'user_assigned_id' => null,
            ]);
        }

        Notification::make()
            ->title('Meeting deleted')
            ->success()
            ->send();
    }
}
