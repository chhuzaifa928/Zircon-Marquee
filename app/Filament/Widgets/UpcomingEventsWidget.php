<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingEventsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Upcoming events';

    public static function canView(): bool
    {
        return auth()->user()?->inAnyRole(User::BOOKING_VIEW) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->with(['customer', 'hall'])
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('event_date', '>=', today())
                    ->orderBy('event_date')
            )
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('event_date')->date('d M Y')->label('Date'),
                TextColumn::make('booking_no')->label('Booking #'),
                TextColumn::make('customer.name')->label('Host'),
                TextColumn::make('hall.name')->label('Hall'),
                TextColumn::make('slot')->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'tentative' => 'warning',
                        'booked' => 'info',
                        'paid' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('due')->money('PKR')->label('Balance'),
            ]);
    }
}
