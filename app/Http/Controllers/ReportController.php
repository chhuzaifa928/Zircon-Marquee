<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\InventoryItem;
use App\Models\Setting;
use App\Models\Voucher;
use App\Services\AccountBalance;
use App\Services\Reports\BookingsMasterReport;
use App\Services\Reports\CashClosingReport;
use App\Services\Reports\ProfitAndLossReport;
use App\Services\Reports\SupplierSummaryReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * A4 PDF exports for the reporting module (SRS §15). Each reads the From–To
 * range from the query string and renders the matching report service output.
 */
class ReportController extends Controller
{
    private function range(Request $request): array
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->endOfMonth()->toDateString());

        return [$from, $to];
    }

    private function stream(string $view, array $data, string $title, string $from, string $to, string $file): Response
    {
        $data['settings'] = Setting::current();
        $data['title'] = $title;
        $data['from'] = $from;
        $data['to'] = $to;
        $data['generatedBy'] = auth()->user()?->name;

        return Pdf::loadView($view, $data)->setPaper('a4')->stream($file.'.pdf');
    }

    public function cashClosing(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        return $this->stream('pdf.reports.cash-closing', [
            'report' => app(CashClosingReport::class)->build($from, $to),
        ], 'Cash Closing Sheet', $from, $to, 'cash-closing');
    }

    public function supplierSummary(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        return $this->stream('pdf.reports.supplier-summary', [
            'report' => app(SupplierSummaryReport::class)->build($from, $to),
        ], 'Supplier Summary', $from, $to, 'supplier-summary');
    }

    public function bookingsMaster(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        return $this->stream('pdf.reports.bookings-master', [
            'report' => app(BookingsMasterReport::class)->build($from, $to),
        ], 'Bookings Master Report', $from, $to, 'bookings-master');
    }

    public function profitLoss(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        return $this->stream('pdf.reports.profit-and-loss', [
            'report' => app(ProfitAndLossReport::class)->build($from, $to),
        ], 'Profit & Loss Statement', $from, $to, 'profit-loss');
    }

    public function inventoryStock(Request $request): Response
    {
        $lowOnly = $request->boolean('low');

        $items = InventoryItem::query()
            ->orderBy('code')
            ->get()
            ->when($lowOnly, fn ($c) => $c->filter->isLowStock()->values());

        $totalValue = round($items->sum(fn (InventoryItem $i): float => $i->stockValue()), 2);

        $data = [
            'settings' => Setting::current(),
            'title' => 'Inventory Stock Report',
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
            'generatedBy' => auth()->user()?->name,
            'items' => $items,
            'totalValue' => $totalValue,
            'lowOnly' => $lowOnly,
        ];

        return Pdf::loadView('pdf.reports.inventory-stock', $data)->setPaper('a4')->stream('inventory-stock.pdf');
    }

    public function generalLedger(Request $request): Response
    {
        [$from, $to] = $this->range($request);
        $account = Account::findOrFail($request->query('account_id'));
        $balances = app(AccountBalance::class);

        $opening = $balances->openingAsOf($account, $from);
        $debitNormal = $balances->isDebitNormal($account);

        $vouchers = Voucher::query()
            ->with(['debitAccount', 'creditAccount'])
            ->where(fn ($q) => $q->where('debit_account_id', $account->id)->orWhere('credit_account_id', $account->id))
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('date')->orderBy('id')
            ->get();

        $running = $opening;
        $rows = $vouchers->map(function (Voucher $v) use ($account, $debitNormal, &$running): array {
            $isDebit = $v->debit_account_id === $account->id;
            $debit = $isDebit ? (float) $v->amount : 0.0;
            $credit = $isDebit ? 0.0 : (float) $v->amount;
            $running += $debitNormal ? ($debit - $credit) : ($credit - $debit);

            return [
                'date' => $v->date,
                'voucher_no' => $v->voucher_no,
                'description' => $v->category ?: $v->remark,
                'contra' => $isDebit ? $v->creditAccount?->name : $v->debitAccount?->name,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($running, 2),
            ];
        });

        return $this->stream('pdf.reports.general-ledger', [
            'account' => $account,
            'opening' => round($opening, 2),
            'rows' => $rows,
            'closing' => round($running, 2),
            'debitNormal' => $debitNormal,
        ], 'General Ledger', $from, $to, 'general-ledger-'.$account->code);
    }
}
