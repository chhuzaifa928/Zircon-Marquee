<?php

namespace App\Filament\Resources\Bookings\Support;

use App\Models\Booking;
use App\Services\AvailabilityService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Reusable row/header actions for the booking lifecycle (SRS §7.6).
 */
class BookingActions
{
    /** Roles allowed to confirm a booking. */
    public static function canConfirm(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accounts']) ?? false;
    }

    /**
     * Confirm a tentative booking → "booked". Blocked when a confirmed booking
     * already holds the hall+date+slot (first confirmed wins).
     */
    public static function confirm(): Action
    {
        return Action::make('confirm')
            ->label('Confirm')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Confirm booking')
            ->modalDescription('This secures the hall for the event date and slot. The slot will be blocked for other bookings.')
            ->visible(fn (Booking $record): bool => $record->isTentative() && static::canConfirm())
            ->action(function (Booking $record): void {
                $conflict = app(AvailabilityService::class)->confirmedConflict(
                    $record->hall_id,
                    $record->event_date->toDateString(),
                    $record->slot,
                    $record->id,
                );

                if ($conflict !== null) {
                    Notification::make()
                        ->danger()
                        ->title('Slot already taken')
                        ->body("{$conflict->booking_no} ({$conflict->customer?->name}) already holds "
                            ."{$conflict->hall?->name} on this date and slot. This booking cannot be confirmed.")
                        ->persistent()
                        ->send();

                    return;
                }

                $record->forceFill([
                    'status' => 'booked',
                    'confirmed_by' => auth()->id(),
                    'confirmed_at' => now(),
                ])->save();

                Notification::make()
                    ->success()
                    ->title('Booking confirmed')
                    ->body("{$record->booking_no} is now booked.")
                    ->send();
            });
    }

    /**
     * Cancel a booking that will not proceed (not available once fully paid).
     */
    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('Cancel')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Cancel booking')
            ->visible(fn (Booking $record): bool => ! $record->isLocked()
                && $record->status !== 'cancelled'
                && (auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accounts', 'sales']) ?? false))
            ->action(function (Booking $record): void {
                $record->forceFill(['status' => 'cancelled'])->save();

                Notification::make()
                    ->success()
                    ->title('Booking cancelled')
                    ->send();
            });
    }

    /**
     * Open the printable Event Booking Sheet (SRS §14.1) as an A4 PDF.
     */
    public static function bookingSheet(): Action
    {
        return Action::make('bookingSheet')
            ->label('Booking sheet')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('gray')
            ->url(fn (Booking $record): string => route('bookings.sheet', $record), shouldOpenInNewTab: true);
    }

    /**
     * Open the printable Advance Receipt (SRS §14.4) as an A4 PDF.
     */
    public static function advanceReceipt(): Action
    {
        return Action::make('advanceReceipt')
            ->label('Advance receipt')
            ->icon(Heroicon::OutlinedReceiptPercent)
            ->color('gray')
            ->url(fn (Booking $record): string => route('bookings.advance-receipt', $record), shouldOpenInNewTab: true);
    }
}
