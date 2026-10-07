<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Atomic, gapless sequence generator.
 *
 * Numbers are allocated inside a transaction using a row-level lock on the
 * number_sequences table, so concurrent requests never collide and never leave
 * gaps. Used for booking_no, slip_no, voucher_no (per type) and customer code.
 * Never use max()+1.
 */
class NumberSequenceService
{
    /**
     * Allocate the next raw integer for the given sequence key.
     */
    public function nextNumber(string $key): int
    {
        return DB::transaction(function () use ($key) {
            $row = DB::table('number_sequences')
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                // Seed the sequence at 1 and claim it.
                DB::table('number_sequences')->insert([
                    'key' => $key,
                    'next' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return 1;
            }

            $current = (int) $row->next;

            DB::table('number_sequences')
                ->where('key', $key)
                ->update(['next' => $current + 1, 'updated_at' => now()]);

            return $current;
        });
    }

    /**
     * Allocate the next formatted number, e.g. next('booking_no', 'BKG-') => "BKG-0001".
     */
    public function next(string $key, string $prefix = '', int $pad = 4): string
    {
        $number = $this->nextNumber($key);

        return $prefix.str_pad((string) $number, $pad, '0', STR_PAD_LEFT);
    }
}
