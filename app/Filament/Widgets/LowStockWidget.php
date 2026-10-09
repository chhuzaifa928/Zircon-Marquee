<?php

namespace App\Filament\Widgets;

use App\Models\InventoryItem;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Low stock items';

    public static function canView(): bool
    {
        return auth()->user()?->inAnyRole(User::INVENTORY) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InventoryItem::query()
                    ->whereColumn('qty_on_hand', '<=', 'reorder_level')
                    ->orderBy('code')
            )
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('All items above reorder level')
            ->columns([
                TextColumn::make('code'),
                TextColumn::make('name'),
                TextColumn::make('qty_on_hand')->label('On hand')->alignEnd(),
                TextColumn::make('reorder_level')->label('Reorder at')->alignEnd(),
                TextColumn::make('unit')->label('Unit'),
            ]);
    }
}
