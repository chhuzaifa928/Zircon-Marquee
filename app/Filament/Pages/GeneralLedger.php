<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Voucher;
use App\Services\AccountBalance;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * General Ledger for one account head (SRS §9.4, §15.3): carried-forward
 * opening balance, every voucher in the period in date order with its contra
 * account, and a running Dr/Cr balance.
 */
class GeneralLedger extends Page
{
    protected string $view = 'filament.pages.general-ledger';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'General Ledger';

    public ?int $accountId = null;

    public ?string $from = null;

    public ?string $to = null;

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->endOfMonth()->toDateString();
        $this->accountId = Account::query()->orderBy('code')->value('id');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accounts']) ?? false;
    }

    /** @return array<int,string> */
    public function getAccountOptionsProperty(): array
    {
        return Account::query()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $a): array => [$a->id => "{$a->code} — {$a->name}"])
            ->all();
    }

    public function getAccountProperty(): ?Account
    {
        return $this->accountId ? Account::find($this->accountId) : null;
    }

    /**
     * Ledger rows for the selected account and period, each carrying a running
     * balance. Returns opening, rows and totals.
     *
     * @return array{opening:float, rows:Collection, totalDebit:float, totalCredit:float, closing:float, debitNormal:bool}|null
     */
    public function getLedgerProperty(): ?array
    {
        $account = $this->account;

        if ($account === null) {
            return null;
        }

        $balances = app(AccountBalance::class);
        $opening = $balances->openingAsOf($account, $this->from);
        $debitNormal = $balances->isDebitNormal($account);

        $vouchers = Voucher::query()
            ->with(['debitAccount', 'creditAccount'])
            ->where(function ($q) use ($account) {
                $q->where('debit_account_id', $account->id)
                    ->orWhere('credit_account_id', $account->id);
            })
            ->when($this->from, fn ($q) => $q->whereDate('date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('date', '<=', $this->to))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $running = $opening;
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        $rows = $vouchers->map(function (Voucher $v) use ($account, $debitNormal, &$running, &$totalDebit, &$totalCredit): array {
            $isDebit = $v->debit_account_id === $account->id;
            $debit = $isDebit ? (float) $v->amount : 0.0;
            $credit = $isDebit ? 0.0 : (float) $v->amount;
            $contra = $isDebit ? $v->creditAccount : $v->debitAccount;

            // Move the running balance on the account's normal side.
            $running += $debitNormal ? ($debit - $credit) : ($credit - $debit);
            $totalDebit += $debit;
            $totalCredit += $credit;

            return [
                'date' => $v->date,
                'voucher_no' => $v->voucher_no,
                'type' => $v->type,
                'description' => $v->category ?: $v->remark,
                'contra' => $contra,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($running, 2),
            ];
        });

        return [
            'opening' => round($opening, 2),
            'rows' => $rows,
            'totalDebit' => round($totalDebit, 2),
            'totalCredit' => round($totalCredit, 2),
            'closing' => round($running, 2),
            'debitNormal' => $debitNormal,
        ];
    }
}
