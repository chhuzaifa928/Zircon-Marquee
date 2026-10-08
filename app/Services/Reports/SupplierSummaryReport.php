<?php

namespace App\Services\Reports;

use App\Models\Account;
use App\Services\AccountBalance;

/**
 * Supplier Summary (SRS §15.4) — the accounts-payable position: per supplier,
 * opening balance, receivings (credits — goods/services taken), payments
 * (debits — paid out), and closing balance owed, with grand totals.
 */
class SupplierSummaryReport
{
    public function __construct(private readonly AccountBalance $balances) {}

    /**
     * @return array{rows:array<int,array<string,mixed>>, totals:array<string,float>}
     */
    public function build(string $from, string $to): array
    {
        $suppliers = Account::query()
            ->where('type', 'supplier')
            ->orderBy('code')
            ->get();

        $rows = [];
        $tOpening = $tReceivings = $tPayments = $tClosing = 0.0;

        foreach ($suppliers as $supplier) {
            $opening = $this->balances->openingAsOf($supplier, $from);

            // Supplier is credit-normal: receivings are credits, payments debits.
            $payments = $this->sum($supplier, 'debit_account_id', $from, $to);
            $receivings = $this->sum($supplier, 'credit_account_id', $from, $to);

            $closing = round($opening + $receivings - $payments, 2);

            $rows[] = [
                'supplier' => $supplier,
                'opening' => round($opening, 2),
                'receivings' => round($receivings, 2),
                'payments' => round($payments, 2),
                'closing' => $closing,
            ];

            $tOpening += $opening;
            $tReceivings += $receivings;
            $tPayments += $payments;
            $tClosing += $closing;
        }

        return [
            'rows' => $rows,
            'totals' => [
                'opening' => round($tOpening, 2),
                'receivings' => round($tReceivings, 2),
                'payments' => round($tPayments, 2),
                'closing' => round($tClosing, 2),
            ],
        ];
    }

    private function sum(Account $account, string $column, string $from, string $to): float
    {
        return (float) \App\Models\Voucher::query()
            ->where($column, $account->id)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->sum('amount');
    }
}
