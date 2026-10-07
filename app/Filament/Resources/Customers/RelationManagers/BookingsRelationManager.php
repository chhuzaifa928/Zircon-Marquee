<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only booking & payment history for a host (SRS §5.3, §5.5). Creation and
 * editing happen in the Booking resource; this is the historical record.
 */
class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Booking history';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('booking_no')
            ->defaultSort('event_date', 'desc')
            ->columns([
                TextColumn::make('booking_no')
                    ->label('Booking #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event_date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('hall.name')
                    ->label('Hall'),
                TextColumn::make('slot')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('event_type')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'tentative' => 'warning',
                        'booked' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('advance_total')
                    ->label('Paid')
                    ->money('PKR'),
                TextColumn::make('due')
                    ->money('PKR'),
            ])
            ->recordUrl(fn ($record): string => BookingResource::getUrl('edit', ['record' => $record]))
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
