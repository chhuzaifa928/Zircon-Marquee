<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingTotals;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Record the salesperson who created the booking (SRS §7.1 "Book By").
        $data['created_by'] = auth()->id();
        $data['status'] = 'tentative';

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Booking $booking */
        $booking = $this->record;

        app(BookingTotals::class)->recalculate($booking);

        $this->warnAboutAvailability($booking);
    }

    /**
     * A tentative booking is always allowed, but warn the salesperson if the
     * slot is already confirmed or contested (SRS §6.3, §6.4).
     */
    protected function warnAboutAvailability(Booking $booking): void
    {
        $availability = app(AvailabilityService::class);
        $date = $booking->event_date->toDateString();

        $conflict = $availability->confirmedConflict($booking->hall_id, $date, $booking->slot, $booking->id);

        if ($conflict !== null) {
            Notification::make()
                ->warning()
                ->title('Slot already confirmed')
                ->body("{$conflict->booking_no} ({$conflict->customer?->name}) already holds this slot. "
                    .'This tentative booking cannot be confirmed until that one is released.')
                ->persistent()
                ->send();

            return;
        }

        $overlaps = $availability->tentativeOverlaps($booking->hall_id, $date, $booking->slot, $booking->id);

        if ($overlaps->isNotEmpty()) {
            Notification::make()
                ->warning()
                ->title('Contested slot')
                ->body("{$overlaps->count()} other tentative booking(s) are competing for this slot. "
                    .'The first to be confirmed wins.')
                ->send();
        }
    }
}
