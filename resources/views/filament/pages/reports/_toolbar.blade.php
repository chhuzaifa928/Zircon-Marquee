{{-- Shared date-range toolbar for report pages (SRS §15.1). --}}
<div class="flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-sm text-gray-500 mb-1">From</label>
        <input type="date" wire:model.live="from"
               class="fi-input block rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600">
    </div>
    <div>
        <label class="block text-sm text-gray-500 mb-1">To</label>
        <input type="date" wire:model.live="to"
               class="fi-input block rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600">
    </div>
    <x-filament::button wire:click="thisMonth" color="gray" size="sm">This month</x-filament::button>
    <x-filament::button wire:click="thisYear" color="gray" size="sm">This year</x-filament::button>
    <span class="text-sm text-gray-400 ml-auto">{{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} — {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</span>
</div>
