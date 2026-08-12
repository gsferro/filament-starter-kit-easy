<x-filament-panels::page>
    @php
        $tabCounts = $this->getTabCounts();
    @endphp

    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTab === 'open'"
            wire:click="$set('activeTab', 'open')"
            icon="heroicon-o-bug-ant"
            :badge="$tabCounts['open']"
            badge-color="danger"
        >
            {{ __('filament-jobs-monitor::translations.open') }}
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'resolved'"
            wire:click="$set('activeTab', 'resolved')"
            icon="heroicon-o-check-circle"
            :badge="$tabCounts['resolved']"
            badge-color="success"
        >
            {{ __('filament-jobs-monitor::translations.resolved') }}
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'all'"
            wire:click="$set('activeTab', 'all')"
            icon="heroicon-o-queue-list"
            :badge="$tabCounts['all']"
        >
            {{ __('filament-jobs-monitor::translations.all_jobs') }}
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{ $this->table }}
</x-filament-panels::page>
