<?php

namespace App\Filament\Resources\PaymentSlips\Pages;

use App\Filament\Resources\PaymentSlips\PaymentSlipResource;
use App\Models\PaymentSlip;
use App\Services\PaymentPosting;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentSlip extends CreateRecord
{
    protected static string $resource = PaymentSlipResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var PaymentSlip $slip */
        $slip = $this->record;
        $booking = $slip->booking;

        $before = $booking->status;
        app(PaymentPosting::class)->apply($booking);
        $booking->refresh();

        // Advance recorded but the slot could not be secured (another booking
        // was confirmed first) — the accountant should know.
        if ($before === 'tentative' && $booking->status === 'tentative') {
            Notification::make()
                ->warning()
                ->title('Payment recorded, booking not confirmed')
                ->body('The hall/date/slot is already held by a confirmed booking, so this booking stays tentative. Review before taking further payment.')
                ->persistent()
                ->send();

            return;
        }

        if ($booking->status === 'paid') {
            Notification::make()
                ->success()
                ->title('Booking fully paid')
                ->body("{$booking->booking_no} is now fully paid and locked.")
                ->send();
        } elseif ($before === 'tentative' && $booking->status === 'booked') {
            Notification::make()
                ->success()
                ->title('Booking confirmed')
                ->body("{$booking->booking_no} is now booked.")
                ->send();
        }
    }
}
