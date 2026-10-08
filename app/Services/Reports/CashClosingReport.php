<?php

namespace App\Services\Reports;

use App\Models\Account;
use App\Models\Voucher;
use App\Services\AccountBalance;

/**
 * Cash Closing Sheet (SRS §15.2) — the treasury report for cash & bank accounts
 * over a period: opening balance, every collection (debit/in) and payment
 * (credit/out) with its remark, and closing balance, with section totals.
 */
class CashClosingReport
{
    public function __construct(private readonly AccountBalance $balances) {}

    /**
     * @return array{accounts:array<int,array<string,mixed>>, totals:array<string,float>}
     */
    public function build(string $from, string $to): array
    {
        $accounts = Account::query()
            ->whereIn('type', ['cash', 'bank'])
            ->orderBy('code')
            ->get();

        $sections = [];
        $grandOpening = $grandIn = $grandOut = $grandClosing = 0.0;

        foreach ($accounts as $account) {
            $opening = $this->balances->openingAsOf($account, $from);

            $vouchers = Voucher::query()
                ->with(['debitAccount', 'creditAccount'])
                ->where(function ($q) use ($account) {
                    $q->where('debit_account_id', $account->id)
                        ->orWhere('credit_account_id', $account->id);
                })
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->orderBy('date')
                ->orderBy('id')
                ->get();

            $rows = [];
            $in = $out = 0.0;

            foreach ($vouchers as $v) {
                $isIn = $v->debit_account_id === $account->id;
                $amount = (float) $v->amount;
                $contra = $isIn ? $v->creditAccount : $v->debitAccount;

                $isIn ? $in += $amount : $out += $amount;

                $rows[] = [
                    'date' => $v->date,
                    'voucher_no' => $v->voucher_no,
                    'remark' => $v->category ?: $v->remark,
                    'contra' => $contra?->name,
                    'in' => $isIn ? $amount : 0.0,
                    'out' => $isIn ? 0.0 : $amount,
                ];
            }

            $closing = round($opening + $in - $out, 2);

            $sections[] = [
                'account' => $account,
                'opening' => round($opening, 2),
                'rows' => $rows,
                'total_in' => round($in, 2),
                'total_out' => round($out, 2),
                'closing' => $closing,
            ];

            $grandOpening += $opening;
            $grandIn += $in;
            $grandOut += $out;
            $grandClosing += $closing;
        }

        return [
            'accounts' => $sections,
            'totals' => [
                'opening' => round($grandOpening, 2),
                'in' => round($grandIn, 2),
                'out' => round($grandOut, 2),
                'closing' => round($grandClosing, 2),
            ],
        ];
    }
}
