<?php

namespace App\Filament\Resources\StockMovements\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date('d M Y')->sortable(),
                TextColumn::make('inventoryItem.name')
                    ->label('Item')
                    ->description(fn ($record): ?string => $record->inventoryItem?->code)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('quantity')->alignEnd()->sortable(),
                TextColumn::make('unit_cost')->money('PKR')->alignEnd()->toggleable(),
                TextColumn::make('reference')->toggleable(),
                TextColumn::make('booking.booking_no')->label('Booking')->toggleable(),
                TextColumn::make('createdBy.name')->label('By')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(['in' => 'In', 'out' => 'Out', 'adjustment' => 'Adjustment']),
                SelectFilter::make('inventory_item_id')
                    ->label('Item')
                    ->relationship('inventoryItem', 'name')
                    ->searchable(),
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
