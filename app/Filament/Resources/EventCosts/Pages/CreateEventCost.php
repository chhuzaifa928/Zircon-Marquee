<?php

namespace App\Filament\Resources\EventCosts\Pages;

use App\Filament\Resources\EventCosts\EventCostResource;
use App\Models\EventCost;
use App\Services\PostingService;
use Filament\Resources\Pages\CreateRecord;

class CreateEventCost extends CreateRecord
{
    protected static string $resource = EventCostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var EventCost $cost */
        $cost = $this->record;

        app(PostingService::class)->postEventCost($cost);
    }
}
