<?php

namespace App\Filament\Resources\InventoryItems\Tables;

use App\Models\InventoryItem;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('qty_on_hand')
                    ->label('On hand')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (string $state, InventoryItem $r): string => rtrim(rtrim(number_format((float) $state, 2), '0'), '.').' '.($r->unit ?: ''))
                    ->badge()
                    ->color(fn (InventoryItem $r): string => $r->isLowStock() ? 'danger' : 'success'),
                TextColumn::make('reorder_level')
                    ->label('Reorder at')
                    ->alignEnd()
                    ->toggleable(),
                TextColumn::make('unit_cost')
                    ->money('PKR')
                    ->alignEnd()
                    ->toggleable(),
                TextColumn::make('stock_value')
                    ->label('Stock value')
                    ->alignEnd()
                    ->state(fn (InventoryItem $r): string => 'PKR '.number_format($r->stockValue(), 2)),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn (): array => InventoryItem::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->pluck('category', 'category')
                        ->all()),
                Filter::make('low_stock')
                    ->label('Low stock only')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('qty_on_hand', '<=', 'reorder_level')),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ]);
    }
}
