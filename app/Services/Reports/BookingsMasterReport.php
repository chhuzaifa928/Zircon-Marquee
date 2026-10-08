<?php

namespace App\Services\Reports;

use App\Models\Booking;

/**
 * Bookings Master Report (SRS §15.5) — every (non-cancelled) event in the range
 * with Total, Payments, Balance, Expense and Net Profit, plus grand totals and
 * an event count. Expense/Net Profit read from event costs (costing module);
 * they are zero until costs are recorded.
 */
class BookingsMasterReport
{
    /**
     * @return array{rows:array<int,array<string,mixed>>, totals:array<string,float>, count:int}
     */
    public function build(string $from, string $to): array
    {
        $bookings = Booking::query()
            ->with(['customer', 'hall'])
            ->withSum('eventCosts as costs_total', 'amount')
            ->where('status', '!=', 'cancelled')
            ->whereDate('event_date', '>=', $from)
            ->whereDate('event_date', '<=', $to)
            ->orderBy('event_date')
            ->get();

        $rows = [];
        $tTotal = $tPayments = $tBalance = $tExpense = $tProfit = 0.0;

        foreach ($bookings as $booking) {
            $total = (float) $booking->grand_total;
            $payments = (float) $booking->advance_total;
            $balance = (float) $booking->due;
            $expense = (float) ($booking->costs_total ?? 0);
            $profit = round($total - $expense, 2);

            $rows[] = [
                'booking' => $booking,
                'total' => $total,
                'payments' => $payments,
                'balance' => $balance,
                'expense' => $expense,
                'profit' => $profit,
            ];

            $tTotal += $total;
            $tPayments += $payments;
            $tBalance += $balance;
            $tExpense += $expense;
            $tProfit += $profit;
        }

        return [
            'rows' => $rows,
            'totals' => [
                'total' => round($tTotal, 2),
                'payments' => round($tPayments, 2),
                'balance' => round($tBalance, 2),
                'expense' => round($tExpense, 2),
                'profit' => round($tProfit, 2),
            ],
            'count' => $bookings->count(),
        ];
    }
}
