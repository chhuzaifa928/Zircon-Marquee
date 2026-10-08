<?php

namespace App\Filament\Resources\EventCosts\Pages;

use App\Filament\Resources\EventCosts\EventCostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventCosts extends ListRecords
{
    protected static string $resource = EventCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
