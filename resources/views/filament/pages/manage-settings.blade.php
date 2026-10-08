<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit" icon="heroicon-m-check">
                Save settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
