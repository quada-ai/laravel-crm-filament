<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class LeadCallsRelationManager extends CallsRelationManager
{
    protected string $view = 'laravel-crm-filament::lead-calls';

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

    public function createCall(): void
    {
        $data = $this->form->getState();

        $call = $this->getOwnerRecord()->calls()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'start_at' => $data['start_at'] ?? null,
            'finish_at' => $data['finish_at'] ?? null,
            'user_owner_id' => $data['user_owner_id'] ?? auth()->id(),
            'user_assigned_id' => $data['user_assigned_id'] ?? null,
            'user_created_id' => auth()->id(),
        ]);

        static::logCrmActivity($this->getOwnerRecord(), $call);

        $this->form->fill([
            'name' => null,
            'description' => null,
            'start_at' => now(),
            'finish_at' => null,
            'user_owner_id' => auth()->id(),
            'user_assigned_id' => null,
        ]);

        Notification::make()
            ->title('Call added')
            ->success()
            ->send();
    }

    public function editCall(int $id): void
    {
        $call = $this->getOwnerRecord()->calls()->whereKey($id)->first();

        if ($call === null) {
            return;
        }

        $this->editingId = (int) $call->id;

        $this->form->fill([
            'name' => $call->name,
            'description' => $call->description,
            'start_at' => $call->start_at,
            'finish_at' => $call->finish_at,
            'user_owner_id' => $call->user_owner_id,
            'user_assigned_id' => $call->user_assigned_id,
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
            'user_owner_id' => auth()->id(),
            'user_assigned_id' => null,
        ]);
    }

    public function updateCall(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $data = $this->form->getState();

        $call = $this->getOwnerRecord()->calls()->whereKey($this->editingId)->first();

        if ($call === null) {
            $this->cancelEdit();

            return;
        }

        $call->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'start_at' => $data['start_at'] ?? null,
            'finish_at' => $data['finish_at'] ?? null,
            'user_owner_id' => $data['user_owner_id'] ?? null,
            'user_assigned_id' => $data['user_assigned_id'] ?? null,
            'user_updated_id' => auth()->id(),
        ]);

        static::logCrmActivity($this->getOwnerRecord(), $call);

        $this->editingId = null;

        $this->form->fill([
            'name' => null,
            'description' => null,
            'start_at' => now(),
            'finish_at' => null,
            'user_owner_id' => auth()->id(),
            'user_assigned_id' => null,
        ]);

        Notification::make()
            ->title('Call updated')
            ->success()
            ->send();
    }

    public function deleteCall(int $id): void
    {
        $call = $this->getOwnerRecord()->calls()->whereKey($id)->first();

        if ($call === null) {
            return;
        }

        $call->delete();

        if ($this->editingId === (int) $id) {
            $this->editingId = null;
            $this->form->fill([
                'name' => null,
                'description' => null,
                'start_at' => now(),
                'finish_at' => null,
                'user_owner_id' => auth()->id(),
                'user_assigned_id' => null,
            ]);
        }

        Notification::make()
            ->title('Call deleted')
            ->success()
            ->send();
    }
}
