<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    protected static ?string $title = 'History';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('laravel-crm-filament::labels.audit.history');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->label(__('laravel-crm-filament::labels.audit.event'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('laravel-crm-filament::labels.audit.user'))
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('changes')
                    ->label(__('laravel-crm-filament::labels.audit.changes'))
                    ->state(fn (Model $record): string => static::formatChanges($record))
                    ->wrap()
                    ->html(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.timestamp'))
                    ->dateTime()
                    ->since()
                    ->tooltip(fn (Model $record): ?string => optional($record->created_at)->toDateTimeString())
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    protected static function formatChanges(Model $record): string
    {
        $old = static::normaliseValues($record->old_values ?? []);
        $new = static::normaliseValues($record->new_values ?? []);

        $fields = array_unique(array_merge(array_keys($old), array_keys($new)));

        if (empty($fields)) {
            return '—';
        }

        $lines = [];
        foreach ($fields as $field) {
            $before = $old[$field] ?? null;
            $after = $new[$field] ?? null;
            $lines[] = sprintf(
                '<strong>%s:</strong> <span style="opacity:0.65">%s</span> &rarr; %s',
                e($field),
                e(static::scalar($before)),
                e(static::scalar($after)),
            );
        }

        return implode('<br>', $lines);
    }

    protected static function normaliseValues(mixed $values): array
    {
        if (is_string($values)) {
            $decoded = json_decode($values, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($values) ? $values : [];
    }

    protected static function scalar(mixed $value): string
    {
        if ($value === null) {
            return '∅';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }
}
