<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Concerns\LogsCrmActivity;

class LeadLunchesRelationManager extends LunchesRelationManager
{
    use LogsCrmActivity;

    protected string $view = 'laravel-crm-filament::lead-lunches';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?int $editingId = null;

    public function isReadOnly(): bool
    {
        return false;
    }

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

    public function createLunch(): void
    {
        $data = $this->form->getState();

        $lunch = $this->getOwnerRecord()->lunches()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'start_at' => $data['start_at'] ?? null,
            'finish_at' => $data['finish_at'] ?? null,
            'location' => $data['location'] ?? null,
            'user_owner_id' => $data['user_owner_id'] ?? auth()->id(),
            'user_assigned_id' => $data['user_assigned_id'] ?? null,
            'user_created_id' => auth()->id(),
        ]);

        static::logCrmActivity($this->getOwnerRecord(), $lunch);

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
            ->title('Lunch added')
            ->success()
            ->send();
    }

    public function editLunch(int $id): void
    {
        $lunch = $this->getOwnerRecord()->lunches()->whereKey($id)->first();

        if ($lunch === null) {
            return;
        }

        $this->editingId = (int) $lunch->id;

        $this->form->fill([
            'name' => $lunch->name,
            'description' => $lunch->description,
            'start_at' => $lunch->start_at,
            'finish_at' => $lunch->finish_at,
            'location' => $lunch->location,
            'user_owner_id' => $lunch->user_owner_id,
            'user_assigned_id' => $lunch->user_assigned_id,
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

    public function updateLunch(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $data = $this->form->getState();

        $lunch = $this->getOwnerRecord()->lunches()->whereKey($this->editingId)->first();

        if ($lunch === null) {
            $this->cancelEdit();

            return;
        }

        $lunch->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'start_at' => $data['start_at'] ?? null,
            'finish_at' => $data['finish_at'] ?? null,
            'location' => $data['location'] ?? null,
            'user_owner_id' => $data['user_owner_id'] ?? null,
            'user_assigned_id' => $data['user_assigned_id'] ?? null,
            'user_updated_id' => auth()->id(),
        ]);

        static::logCrmActivity($this->getOwnerRecord(), $lunch);

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
            ->title('Lunch updated')
            ->success()
            ->send();
    }

    public function deleteLunch(int $id): void
    {
        $lunch = $this->getOwnerRecord()->lunches()->whereKey($id)->first();

        if ($lunch === null) {
            return;
        }

        $lunch->delete();

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
            ->title('Lunch deleted')
            ->success()
            ->send();
    }
}
