<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Filament\Resources\Bookings\Support\BookingActions;
use App\Models\Booking;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('event_date', 'desc')
            ->columns([
                TextColumn::make('booking_no')
                    ->label('Booking #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Host')
                    ->description(fn (Booking $r): ?string => $r->customer?->code)
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
                TextColumn::make('guests')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('advance_total')
                    ->label('Advance')
                    ->money('PKR')
                    ->toggleable(),
                TextColumn::make('due')
                    ->money('PKR')
                    ->sortable(),
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
                TextColumn::make('createdBy.name')
                    ->label('Booked by')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(Booking::STATUSES)
                        ->mapWithKeys(fn (string $s): array => [$s => ucfirst($s)])
                        ->all()),
                SelectFilter::make('hall_id')
                    ->label('Hall')
                    ->relationship('hall', 'name'),
                SelectFilter::make('slot')
                    ->options(['lunch' => 'Lunch', 'dinner' => 'Dinner']),
                Filter::make('upcoming')
                    ->label('Upcoming events only')
                    ->query(fn (Builder $query): Builder => $query->whereDate('event_date', '>=', today())),
            ])
            ->recordActions([
                BookingActions::confirm(),
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (Booking $record): bool => ! $record->isLocked()),
                    BookingActions::bookingSheet(),
                    BookingActions::advanceReceipt(),
                    BookingActions::cancel(),
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
