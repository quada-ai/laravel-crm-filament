<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrmFilament\Concerns\LogsCrmActivity;

class FilesRelationManager extends RelationManager
{
    use LogsCrmActivity;

    protected static string $relationship = 'files';

    protected static ?string $title = 'Files';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('laravel-crm-filament::labels.resources.file.plural') ?? 'Files';
    }

    public function form(Schema $schema): Schema
    {
        $disk = config('laravel-crm.upload_disk', 'public');

        return $schema->components([
            Forms\Components\FileUpload::make('file')
                ->label(__('laravel-crm-filament::labels.file.file'))
                ->disk($disk)
                ->directory(fn (RelationManager $livewire): string => 'laravel-crm/' . strtolower(class_basename($livewire->getOwnerRecord())) . '/' . $livewire->getOwnerRecord()->id . '/files')
                ->required()
                ->preserveFilenames()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('title')
                ->label(__('laravel-crm-filament::labels.fields.description'))
                ->maxLength(255),
            Forms\Components\TextInput::make('format')
                ->label(__('laravel-crm-filament::labels.file.file_type'))
                ->maxLength(255)
                ->helperText('Optional category, e.g. contract, invoice, photo.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->limit(60)->wrap(),
                Tables\Columns\TextColumn::make('title')->label(__('laravel-crm-filament::labels.fields.description'))->limit(40)->toggleable(),
                Tables\Columns\TextColumn::make('format')->label(__('laravel-crm-filament::labels.fields.type'))->badge()->toggleable(),
                Tables\Columns\TextColumn::make('mime')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('createdByUser.name')
                    ->label(__('laravel-crm-filament::labels.file.uploaded_by'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Actions\CreateAction::make()
                    ->using(function (array $data, RelationManager $livewire): Model {
                        $disk = config('laravel-crm.upload_disk', 'public');
                        $path = $data['file'];
                        $owner = $livewire->getOwnerRecord();

                        $name = basename($path);
                        $filesize = null;
                        $mime = null;
                        $format = $data['format'] ?? null;

                        $storage = Storage::disk($disk);
                        if ($storage->exists($path)) {
                            $filesize = $storage->size($path);
                            $mime = $storage->mimeType($path);
                            if (! $format) {
                                $format = pathinfo($path, PATHINFO_EXTENSION) ?: null;
                            }
                        }

                        return $owner->files()->create([
                            'external_id' => Uuid::uuid4()->toString(),
                            'file' => $path,
                            'name' => $name,
                            'title' => $data['title'] ?? null,
                            'format' => $format,
                            'filesize' => $filesize,
                            'mime' => $mime,
                            'disk' => $disk,
                        ]);
                    })
                    ->after(fn (Model $record, RelationManager $livewire) => static::logCrmActivity($livewire->getOwnerRecord(), $record)),
            ])
            ->recordActions([
                Actions\Action::make('download')
                    ->label(__('laravel-crm-filament::labels.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (File $record): ?string => static::buildDownloadUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (File $record): bool => static::buildDownloadUrl($record) !== null),
                Actions\DeleteAction::make(),
            ]);
    }

    protected static function buildDownloadUrl(File $record): ?string
    {
        if (! $record->file) {
            return null;
        }

        $disk = $record->disk ?: config('laravel-crm.upload_disk', 'public');
        $storage = Storage::disk($disk);

        if (! $storage->exists($record->file)) {
            return null;
        }

        try {
            return $storage->temporaryUrl($record->file, now()->addMinutes(5));
        } catch (\Throwable $e) {
            return $storage->url($record->file);
        }
    }
}
