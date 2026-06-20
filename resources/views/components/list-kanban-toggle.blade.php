@props([
    'current' => 'list',
    'listUrl' => '#',
    'kanbanUrl' => '#',
])
@php
    $listLabel = __('laravel-crm-filament::labels.actions.list_view');
    $kanbanLabel = __('laravel-crm-filament::labels.actions.kanban_view');
    $listActive = $current === 'list';
    $kanbanActive = $current === 'kanban';
@endphp
@once
    <style>
        .fi-segmented-control {
            --crm-seg-bg: rgb(243 244 246);
            --crm-seg-border: rgb(229 231 235);
            --crm-seg-active-bg: #ffffff;
            --crm-seg-active-color: #0d9488;
            --crm-seg-inactive-color: rgb(107 114 128);
            --crm-seg-active-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);

            display: inline-flex;
            align-items: center;
            padding: 4px;
            background: var(--crm-seg-bg);
            border: 1px solid var(--crm-seg-border);
            border-radius: 8px;
            gap: 2px;
        }
        .dark .fi-segmented-control {
            --crm-seg-bg: rgb(39 39 42);
            --crm-seg-border: rgb(63 63 70);
            --crm-seg-active-bg: rgb(63 63 70);
            --crm-seg-active-color: #2dd4bf;
            --crm-seg-inactive-color: rgb(161 161 170);
            --crm-seg-active-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
        }
        .fi-segmented-control__link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 32px;
            width: 36px;
            border-radius: 6px;
            text-decoration: none;
            color: var(--crm-seg-inactive-color);
            background: transparent;
            transition: background-color 0.15s, color 0.15s;
        }
        .fi-segmented-control__link--active {
            background: var(--crm-seg-active-bg);
            color: var(--crm-seg-active-color);
            box-shadow: var(--crm-seg-active-shadow);
        }
        .fi-segmented-control__link svg {
            height: 20px;
            width: 20px;
        }
    </style>
@endonce
<div class="fi-segmented-control" role="group">
    <a href="{{ $listUrl }}"
       aria-label="{{ $listLabel }}"
       title="{{ $listLabel }}"
       wire:navigate
       class="fi-segmented-control__link{{ $listActive ? ' fi-segmented-control__link--active' : '' }}">
        <x-filament::icon icon="heroicon-o-list-bullet" />
    </a>
    <a href="{{ $kanbanUrl }}"
       aria-label="{{ $kanbanLabel }}"
       title="{{ $kanbanLabel }}"
       wire:navigate
       class="fi-segmented-control__link{{ $kanbanActive ? ' fi-segmented-control__link--active' : '' }}">
        <x-filament::icon icon="heroicon-o-view-columns" />
    </a>
</div>
