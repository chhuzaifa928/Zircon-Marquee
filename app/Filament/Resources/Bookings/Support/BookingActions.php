<?php

namespace App\Filament\Resources\Bookings\Support;

use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\PostingService;
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

    /**
     * Close the event and recognise revenue (posting #2). Accounts-only; the
     * booking must be fully paid and not already closed.
     */
    public static function closeEvent(): Action
    {
        return Action::make('closeEvent')
            ->label('Close event & recognise revenue')
            ->icon(Heroicon::OutlinedFlag)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Close event and recognise revenue')
            ->modalDescription('Posts the revenue-recognition vouchers (Dr Customer Advances; Cr income heads + GST) and opens event costing. This cannot be undone except by a Super Admin.')
            ->visible(fn (Booking $record): bool => $record->status === 'paid'
                && ! $record->isClosed()
                && static::canConfirm())
            ->action(function (Booking $record): void {
                app(PostingService::class)->postRevenueRecognition($record);

                Notification::make()
                    ->success()
                    ->title('Event closed')
                    ->body("Revenue recognised for {$record->booking_no}. Costing is now open.")
                    ->send();
            });
    }

    /**
     * Reverse a revenue-recognition batch and reopen the event. Super Admin only.
     */
    public static function reopenEvent(): Action
    {
        return Action::make('reopenEvent')
            ->label('Reverse revenue & reopen')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (Booking $record): bool => $record->isClosed()
                && (auth()->user()?->isSuperAdmin() ?? false))
            ->action(function (Booking $record): void {
                app(PostingService::class)->reverseRevenueRecognition($record);

                Notification::make()
                    ->success()
                    ->title('Event reopened')
                    ->body("Revenue vouchers for {$record->booking_no} reversed.")
                    ->send();
            });
    }
}
