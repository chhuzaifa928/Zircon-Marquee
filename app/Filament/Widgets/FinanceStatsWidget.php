<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Booking;
use App\Models\User;
use App\Services\AccountBalance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->inAnyRole(User::ACCOUNTING) ?? false;
    }

    protected function getStats(): array
    {
        $outstanding = (float) Booking::where('status', '!=', 'cancelled')->sum('due');

        $monthRevenue = (float) Booking::whereIn('status', ['paid'])
            ->whereBetween('event_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('grand_total');

        $balances = app(AccountBalance::class);
        $cash = Account::whereIn('type', ['cash', 'bank'])->where('is_active', true)->get()
            ->sum(fn (Account $a): float => $balances->balance($a));

        $fmt = fn (float $n): string => 'PKR '.number_format($n, 0);

        return [
            Stat::make('Outstanding due', $fmt($outstanding))
                ->description('Across active bookings')
                ->color($outstanding > 0 ? 'warning' : 'success'),
            Stat::make('Revenue this month', $fmt($monthRevenue))
                ->description('Fully-paid events')
                ->color('success'),
            Stat::make('Cash & bank on hand', $fmt($cash))
                ->color('primary'),
        ];
    }
}
