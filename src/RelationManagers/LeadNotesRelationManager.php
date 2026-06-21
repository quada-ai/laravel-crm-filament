<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;

class LeadNotesRelationManager extends NotesRelationManager
{
    protected string $view = 'laravel-crm-filament::lead-notes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Textarea::make('content')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('noted_at'),
        ]);
    }
}
