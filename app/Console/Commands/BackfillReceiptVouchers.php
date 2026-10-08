<?php

namespace App\Console\Commands;

use App\Services\PostingService;
use Illuminate\Console\Command;

/**
 * Posts Receipt Vouchers for payment slips recorded before auto-posting existed
 * (Step 3 slips). Safe to re-run — slips that already carry a voucher are
 * skipped.
 */
class BackfillReceiptVouchers extends Command
{
    protected $signature = 'accounting:backfill-receipts';

    protected $description = 'Post Receipt Vouchers for payment slips that have none yet';

    public function handle(PostingService $posting): int
    {
        $count = $posting->backfillReceipts();

        $this->info("Posted {$count} Receipt Voucher(s) for existing payment slips.");

        return self::SUCCESS;
    }
}
