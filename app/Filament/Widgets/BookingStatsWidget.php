<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->inAnyRole(User::BOOKING_VIEW) ?? false;
    }

    protected function getStats(): array
    {
        $tentative = Booking::where('status', 'tentative')->count();

        $upcoming = Booking::where('status', '!=', 'cancelled')
            ->whereDate('event_date', '>=', today())
            ->count();

        $thisMonth = Booking::where('status', '!=', 'cancelled')
            ->whereBetween('event_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return [
            Stat::make('Tentative bookings', $tentative)
                ->description('Awaiting confirmation')
                ->color('warning'),
            Stat::make('Upcoming events', $upcoming)
                ->description('From today onward')
                ->color('info'),
            Stat::make('Events this month', $thisMonth)
                ->color('success'),
        ];
    }
}
