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

    $wrapStyle = 'display:inline-flex;align-items:center;padding:4px;background:rgb(243 244 246);border:1px solid rgb(229 231 235);border-radius:8px;gap:2px;';
    $baseLink = 'display:inline-flex;align-items:center;justify-content:center;height:32px;width:36px;border-radius:6px;text-decoration:none;transition:background-color .15s,color .15s;';
    $activeStyle = 'background:white;color:#0d9488;box-shadow:0 1px 2px rgba(0,0,0,.06);';
    $inactiveStyle = 'background:transparent;color:rgb(107 114 128);';
@endphp
<div class="fi-segmented-control" style="{{ $wrapStyle }}" role="group">
    <a href="{{ $listUrl }}"
       aria-label="{{ $listLabel }}"
       title="{{ $listLabel }}"
       wire:navigate
       style="{{ $baseLink }}{{ $listActive ? $activeStyle : $inactiveStyle }}">
        <x-filament::icon icon="heroicon-o-list-bullet" style="height:20px;width:20px;" />
    </a>
    <a href="{{ $kanbanUrl }}"
       aria-label="{{ $kanbanLabel }}"
       title="{{ $kanbanLabel }}"
       wire:navigate
       style="{{ $baseLink }}{{ $kanbanActive ? $activeStyle : $inactiveStyle }}">
        <x-filament::icon icon="heroicon-o-view-columns" style="height:20px;width:20px;" />
    </a>
</div>
