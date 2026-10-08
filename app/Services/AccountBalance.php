<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Voucher;

/**
 * Computes account balances from the opening balance plus posted vouchers
 * (SRS §9.1, §9.4). Balances are always derived, never stored.
 *
 * Each account type has a "normal" side:
 *   - cash / bank / staff / expense are debit-normal  → debits increase
 *   - income / supplier are credit-normal             → credits increase
 *
 * Balances are expressed on the account's normal side: a positive number is a
 * normal balance, a negative number the reverse. Opening balances are stored
 * on the normal side already.
 */
class AccountBalance
{
    public const DEBIT_NORMAL = ['cash', 'bank', 'staff', 'asset', 'expense'];

    /** Whether the account increases on the debit side. */
    public function isDebitNormal(Account $account): bool
    {
        return in_array($account->type, self::DEBIT_NORMAL, true);
    }

    /**
     * Net movement (debits − credits, debit-positive) posted to the account,
     * optionally bounded by an inclusive date range.
     */
    public function movement(Account $account, ?string $from = null, ?string $to = null): float
    {
        $debit = $this->sumSide($account, 'debit_account_id', $from, $to);
        $credit = $this->sumSide($account, 'credit_account_id', $from, $to);

        return round($debit - $credit, 2);
    }

    /**
     * Carried-forward balance strictly before $from (opening + all prior
     * movement), signed on the normal side. With no $from, just the opening.
     */
    public function openingAsOf(Account $account, ?string $from = null): float
    {
        $opening = (float) $account->opening_balance;

        if ($from === null) {
            return round($opening, 2);
        }

        $prior = $this->movement($account, null, $this->dayBefore($from));

        return round($opening + $this->toNormal($prior, $account), 2);
    }

    /**
     * Current balance = opening + net movement, signed on the normal side. With
     * a range, the closing balance as of $to.
     */
    public function balance(Account $account, ?string $from = null, ?string $to = null): float
    {
        $opening = $this->openingAsOf($account, $from);
        $movement = $this->toNormal($this->movement($account, $from, $to), $account);

        return round($opening + $movement, 2);
    }

    private function sumSide(Account $account, string $column, ?string $from, ?string $to): float
    {
        return (float) Voucher::query()
            ->where($column, $account->id)
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->sum('amount');
    }

    private function toNormal(float $debitPositive, Account $account): float
    {
        return $this->isDebitNormal($account) ? $debitPositive : -$debitPositive;
    }

    private function dayBefore(string $date): string
    {
        return \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
    }
}
