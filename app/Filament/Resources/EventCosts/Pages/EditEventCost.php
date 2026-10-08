<?php

namespace App\Filament\Resources\EventCosts\Pages;

use App\Filament\Resources\EventCosts\EventCostResource;
use App\Models\EventCost;
use App\Services\PostingService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventCost extends EditRecord
{
    protected static string $resource = EventCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(fn (EventCost $record) => app(PostingService::class)->reverseEventCost($record)),
        ];
    }

    protected function beforeSave(): void
    {
        // Reverse the existing posting; it is re-posted after the save.
        app(PostingService::class)->reverseEventCost($this->record);
    }

    protected function afterSave(): void
    {
        /** @var EventCost $cost */
        $cost = $this->record->refresh();

        app(PostingService::class)->postEventCost($cost);
    }
}
