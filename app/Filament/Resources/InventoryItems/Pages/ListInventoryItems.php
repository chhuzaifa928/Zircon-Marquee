<?php

namespace App\Filament\Resources\InventoryItems\Pages;

use App\Filament\Resources\InventoryItems\InventoryItemResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListInventoryItems extends ListRecords
{
    protected static string $resource = InventoryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ActionGroup::make([
                Action::make('stockReport')
                    ->label('Stock report (PDF)')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (): string => route('reports.inventory-stock'), shouldOpenInNewTab: true),
                Action::make('lowStockReport')
                    ->label('Low-stock report (PDF)')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->url(fn (): string => route('reports.inventory-stock', ['low' => 1]), shouldOpenInNewTab: true),
            ])
                ->label('Reports')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->button(),
        ];
    }
}
