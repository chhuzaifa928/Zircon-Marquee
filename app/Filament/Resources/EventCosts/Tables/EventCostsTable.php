<?php

namespace App\Filament\Resources\EventCosts\Tables;

use App\Models\EventCost;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EventCostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date('d M Y')->sortable(),
                TextColumn::make('booking.booking_no')
                    ->label('Booking')
                    ->description(fn (EventCost $r): ?string => $r->booking?->customer?->name)
                    ->searchable(),
                TextColumn::make('cost_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'vendor' => 'info',
                        'inventory' => 'warning',
                        'misc' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('reference_name')
                    ->label('Vendor / item')
                    ->state(fn (EventCost $r): string => $r->vendor?->name ?? $r->inventoryItem?->name ?? '—'),
                TextColumn::make('description')->toggleable(),
                TextColumn::make('amount')
                    ->money('PKR')
                    ->alignEnd()
                    ->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('PKR')),
                TextColumn::make('voucher.voucher_no')->label('Voucher')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('cost_type')
                    ->options(['vendor' => 'Vendor', 'inventory' => 'Inventory', 'misc' => 'Misc']),
                SelectFilter::make('booking_id')
                    ->label('Booking')
                    ->relationship('booking', 'booking_no')
                    ->searchable(),
            ])
            ->recordActions([
                ActionGroup::make([ViewAction::make(), EditAction::make()]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ]);
    }
}
