<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;

class LeadNotesRelationManager extends NotesRelationManager
{
    protected string $view = 'laravel-crm-filament::lead-notes';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'content' => null,
            'noted_at' => now(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('noted_at'),
            ]);
    }

    public function createNote(): void
    {
        $data = $this->form->getState();

        $note = $this->getOwnerRecord()->notes()->create([
            'content' => $data['content'],
            'noted_at' => $data['noted_at'] ?? null,
            'user_created_id' => auth()->id(),
        ]);

        static::logCrmActivity($this->getOwnerRecord(), $note);

        $this->form->fill([
            'content' => null,
            'noted_at' => now(),
        ]);

        Notification::make()
            ->title('Note added')
            ->success()
            ->send();
    }
}
