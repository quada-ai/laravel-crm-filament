<?php

namespace VentureDrake\LaravelCrmFilament\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;

class CalendarPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $slug = 'calendar';

    protected static ?int $navigationSort = 30;

    protected string $view = 'laravel-crm-filament::calendar.index';

    public ?int $ownerFilter = null;

    /**
     * @var array<string,bool>
     */
    public array $typeFilters = [
        'task' => true,
        'call' => true,
        'meeting' => true,
        'lunch' => true,
    ];

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('laravel-crm-filament::labels.pages.calendar.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('laravel-crm-filament::labels.pages.calendar.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup()
            ?? __('laravel-crm-filament::labels.navigation.groups.activity');
    }

    public function updatedOwnerFilter(): void
    {
        $this->dispatch('calendar-refetch');
    }

    public function updatedTypeFilters(): void
    {
        $this->dispatch('calendar-refetch');
    }

    public function getOwners(): Collection
    {
        return collect(\VentureDrake\LaravelCrmFilament\Support\UserOptions::get());
    }

    /**
     * Returns FullCalendar-shaped events for the given date window and current filter state.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getEventsForRange(string $start, string $end): array
    {
        $startCarbon = Carbon::parse($start);
        $endCarbon = Carbon::parse($end);

        $events = [];

        if ($this->typeFilters['task'] ?? false) {
            $tasks = Task::query()
                ->whereNotNull('due_at')
                ->whereBetween('due_at', [$startCarbon, $endCarbon])
                ->when($this->ownerFilter, fn ($q) => $q->where('user_owner_id', $this->ownerFilter))
                ->limit(1000)
                ->get();
            foreach ($tasks as $task) {
                $events[] = [
                    'id' => 'task:' . $task->external_id,
                    'title' => $task->name,
                    'start' => optional($task->due_at)->toIso8601String(),
                    'allDay' => false,
                    'extendedProps' => [
                        'type' => 'task',
                        'externalId' => $task->external_id,
                        'completed' => $task->completed_at !== null,
                    ],
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                ];
            }
        }

        $eventTypes = [
            'call' => [Call::class, '#3b82f6'],
            'meeting' => [Meeting::class, '#10b981'],
            'lunch' => [Lunch::class, '#a855f7'],
        ];

        foreach ($eventTypes as $type => [$class, $color]) {
            if (! ($this->typeFilters[$type] ?? false)) {
                continue;
            }

            $records = $class::query()
                ->whereNotNull('start_at')
                ->whereBetween('start_at', [$startCarbon, $endCarbon])
                ->when($this->ownerFilter, fn ($q) => $q->where('user_owner_id', $this->ownerFilter))
                ->limit(1000)
                ->get();

            foreach ($records as $record) {
                $events[] = [
                    'id' => $type . ':' . $record->external_id,
                    'title' => $record->name ?? ucfirst($type),
                    'start' => optional($record->start_at)->toIso8601String(),
                    'allDay' => false,
                    'extendedProps' => [
                        'type' => $type,
                        'externalId' => $record->external_id,
                    ],
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                ];
            }
        }

        return $events;
    }

    /**
     * Drag-to-reschedule handler called from the FullCalendar `eventDrop` hook.
     */
    public function moveEvent(string $id, string $type, string $newDate): void
    {
        $record = match ($type) {
            'task' => Task::query()->where('external_id', $id)->first(),
            'call' => Call::query()->where('external_id', $id)->first(),
            'meeting' => Meeting::query()->where('external_id', $id)->first(),
            'lunch' => Lunch::query()->where('external_id', $id)->first(),
            default => null,
        };

        if (! $record) {
            return;
        }

        $column = $type === 'task' ? 'due_at' : 'start_at';
        $record->forceFill([$column => $newDate])->save();

        $user = auth()->user();
        if ($user && method_exists($record, 'getMorphClass')) {
            $timelineableType = $record->getMorphClass();
            $timelineableId = $record->id;

            // Tasks/Calls/Meetings/Lunches own a polymorphic parent — prefer it as
            // the timeline owner so the activity surfaces on the parent's feed.
            $parentType = $type === 'task' ? 'taskable_type' : ($type . 'able_type');
            $parentId = $type === 'task' ? 'taskable_id' : ($type . 'able_id');
            if (! empty($record->{$parentType}) && ! empty($record->{$parentId})) {
                $timelineableType = $record->{$parentType};
                $timelineableId = $record->{$parentId};
            }

            Activity::create([
                'causeable_type' => $user->getMorphClass(),
                'causeable_id' => $user->id,
                'timelineable_type' => $timelineableType,
                'timelineable_id' => $timelineableId,
                'recordable_type' => $record->getMorphClass(),
                'recordable_id' => $record->id,
            ]);
        }
    }
}
