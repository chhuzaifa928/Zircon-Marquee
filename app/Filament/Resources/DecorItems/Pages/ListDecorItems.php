<?php

namespace App\Filament\Resources\DecorItems\Pages;

use App\Filament\Resources\DecorItems\DecorItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDecorItems extends ListRecords
{
    protected static string $resource = DecorItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
