<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Booking;
use App\Models\PaymentSlip;
use App\Models\Voucher;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Auto-posts the double-entry vouchers defined in docs/posting-policy.md.
 *
 * Every posting is a balanced two-sided voucher. A multi-line posting (revenue
 * recognition) is a set of paired vouchers sharing a batch_uid so it can be
 * reversed as one unit. Each posted voucher links back to its source
 * (payment slip or booking).
 */
class PostingService
{
    public const CUSTOMER_ADVANCES = '2100';

    public const GST_PAYABLE = '2200';

    public function accountByCode(string $code): ?Account
    {
        return Account::where('code', $code)->first();
    }

    // ----- Posting #1: payment slip → Receipt Voucher -----

    /**
     * Dr Cash/Bank (received into) · Cr Customer Advances (2100), gross amount.
     * Idempotent: a slip that already carries a voucher is left untouched.
     */
    public function postReceipt(PaymentSlip $slip): ?Voucher
    {
        if ($slip->voucher_id !== null) {
            return $slip->voucher;
        }

        $advances = $this->accountByCode(self::CUSTOMER_ADVANCES);

        if ($advances === null || $slip->account_id === null) {
            return null; // chart not ready — nothing to post
        }

        return DB::transaction(function () use ($slip, $advances) {
            $booking = $slip->booking;

            $voucher = Voucher::create([
                'type' => 'RV',
                'date' => $slip->date,
                'category' => 'Banquet Advance',
                'remark' => 'Advance for '.$booking?->booking_no.' — '.$booking?->customer?->name,
                'debit_account_id' => $slip->account_id,
                'credit_account_id' => $advances->id,
                'amount' => $slip->amount,
                'source_type' => PaymentSlip::class,
                'source_id' => $slip->id,
                'created_by' => $slip->created_by,
            ]);

            $slip->forceFill(['voucher_id' => $voucher->id])->saveQuietly();

            return $voucher;
        });
    }

    /** Soft-delete the slip's Receipt Voucher and unlink it. */
    public function reverseReceipt(PaymentSlip $slip): void
    {
        if ($slip->voucher_id !== null) {
            Voucher::whereKey($slip->voucher_id)->get()->each->delete();
            $slip->forceFill(['voucher_id' => null])->saveQuietly();
        }
    }

    // ----- Posting #2: Close Event → revenue recognition -----

    /**
     * Dr Customer Advances (2100) · Cr each income head (net of GST) · Cr GST
     * Payable (2200). Posted as paired JV vouchers sharing a batch_uid.
     * Idempotent on event_closed_at.
     */
    public function postRevenueRecognition(Booking $booking): void
    {
        if ($booking->isClosed()) {
            return;
        }

        $advances = $this->accountByCode(self::CUSTOMER_ADVANCES);

        if ($advances === null) {
            throw new \RuntimeException('Customer Advances (2100) account is missing from the chart of accounts.');
        }

        DB::transaction(function () use ($booking, $advances) {
            $batch = (string) Str::uuid();

            foreach ($this->revenueLines($booking) as [$code, $amount]) {
                if ($amount <= 0) {
                    continue;
                }

                $head = $this->accountByCode($code);

                if ($head !== null) {
                    $this->journal($advances->id, $head->id, $amount, $booking, $batch, 'Event Revenue');
                }
            }

            $gstAmount = (float) $booking->gst_amount;
            $gstPayable = $this->accountByCode(self::GST_PAYABLE);

            if ($gstAmount > 0 && $gstPayable !== null) {
                $this->journal($advances->id, $gstPayable->id, $gstAmount, $booking, $batch, 'Event Revenue — GST');
            }

            $booking->forceFill(['event_closed_at' => now()])->saveQuietly();
        });
    }

    /** Soft-delete the revenue-recognition batch and reopen the event. */
    public function reverseRevenueRecognition(Booking $booking): void
    {
        Voucher::query()
            ->where('source_type', Booking::class)
            ->where('source_id', $booking->id)
            ->where('type', 'JV')
            ->get()
            ->each
            ->delete();

        $booking->forceFill(['event_closed_at' => null])->saveQuietly();
    }

    /**
     * Income lines for a booking, keyed by income-head code and summed per head
     * (charge types that share a head are combined). Amounts are the stored
     * pre-GST figures, so no back-calculation is needed.
     *
     * @return array<int,array{0:string,1:float}>
     */
    private function revenueLines(Booking $booking): array
    {
        $map = ChartOfAccountsSeeder::CHARGE_INCOME_MAP;
        $lines = [];

        // Per-head (menu / venue) → Food Sales.
        $lines[$map['menu']] = (float) $booking->headcharge_total;

        foreach ($booking->charges as $charge) {
            $code = $map[$charge->charge_type] ?? $map['other_charges'];
            $lines[$code] = ($lines[$code] ?? 0) + (float) $charge->amount;
        }

        return collect($lines)
            ->map(fn (float $amount, string $code): array => [$code, round($amount, 2)])
            ->values()
            ->all();
    }

    private function journal(int $debitId, int $creditId, float $amount, Booking $booking, string $batch, string $category): Voucher
    {
        return Voucher::create([
            'type' => 'JV',
            'batch_uid' => $batch,
            'date' => now()->toDateString(),
            'category' => $category,
            'remark' => 'Revenue recognition for '.$booking->booking_no,
            'debit_account_id' => $debitId,
            'credit_account_id' => $creditId,
            'amount' => round($amount, 2),
            'source_type' => Booking::class,
            'source_id' => $booking->id,
            'created_by' => auth()->id(),
        ]);
    }

    // ----- Backfill (Step 3 slips posted before auto-posting existed) -----

    public function backfillReceipts(): int
    {
        $count = 0;

        PaymentSlip::query()
            ->whereNull('voucher_id')
            ->with('booking.customer')
            ->chunkById(100, function ($slips) use (&$count) {
                foreach ($slips as $slip) {
                    if ($this->postReceipt($slip) !== null) {
                        $count++;
                    }
                }
            });

        return $count;
    }
}
