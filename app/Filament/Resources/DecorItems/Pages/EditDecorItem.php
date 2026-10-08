<?php

namespace App\Filament\Resources\DecorItems\Pages;

use App\Filament\Resources\DecorItems\DecorItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDecorItem extends EditRecord
{
    protected static string $resource = DecorItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
