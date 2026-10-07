<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Support\BookingActions;
use App\Models\Booking;
use App\Services\BookingTotals;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BookingActions::confirm(),
            BookingActions::cancel(),
            BookingActions::bookingSheet(),
            DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        /** @var Booking $booking */
        $booking = $this->record;

        app(BookingTotals::class)->recalculate($booking);
    }
}
