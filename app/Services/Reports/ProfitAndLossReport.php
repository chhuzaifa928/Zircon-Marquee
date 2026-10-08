<?php

namespace App\Services\Reports;

use App\Models\Account;
use App\Models\Voucher;

/**
 * Profit & Loss Statement (SRS §15.6) — income heads versus expense heads for
 * the period, with Net Profit (a loss shown in parentheses by the view).
 *
 * Period activity only (not cumulative balances): income = credits − debits in
 * range per income head; expense = debits − credits in range per expense head.
 */
class ProfitAndLossReport
{
    /**
     * @return array{income:array<int,array<string,mixed>>, expense:array<int,array<string,mixed>>, totalIncome:float, totalExpense:float, netProfit:float}
     */
    public function build(string $from, string $to): array
    {
        $income = $this->lines('income', creditPositive: true, from: $from, to: $to);
        $expense = $this->lines('expense', creditPositive: false, from: $from, to: $to);

        $totalIncome = round(array_sum(array_column($income, 'amount')), 2);
        $totalExpense = round(array_sum(array_column($expense, 'amount')), 2);

        return [
            'income' => $income,
            'expense' => $expense,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netProfit' => round($totalIncome - $totalExpense, 2),
        ];
    }

    /**
     * @return array<int,array{account:Account, amount:float}>
     */
    private function lines(string $type, bool $creditPositive, string $from, string $to): array
    {
        return Account::query()
            ->where('type', $type)
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($creditPositive, $from, $to): array {
                $debit = $this->sum($account, 'debit_account_id', $from, $to);
                $credit = $this->sum($account, 'credit_account_id', $from, $to);
                $amount = $creditPositive ? ($credit - $debit) : ($debit - $credit);

                return ['account' => $account, 'amount' => round($amount, 2)];
            })
            ->all();
    }

    private function sum(Account $account, string $column, string $from, string $to): float
    {
        return (float) Voucher::query()
            ->where($column, $account->id)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->sum('amount');
    }
}
