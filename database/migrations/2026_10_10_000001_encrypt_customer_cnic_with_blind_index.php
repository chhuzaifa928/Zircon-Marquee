<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypt customer CNIC at rest and add a deterministic blind index for exact
 * lookups (security hardening). The CNIC column is widened to text to hold the
 * ciphertext; cnic_index stores an HMAC of the normalised (digits-only) value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('cnic')->nullable()->change();
            $table->string('cnic_index', 64)->nullable()->after('cnic')->index();
        });

        // Backfill existing rows: encrypt the current plaintext and index it.
        DB::table('customers')->whereNotNull('cnic')->orderBy('id')->each(function ($row) {
            $plain = $row->cnic;

            // Skip values that are already ciphertext (idempotent re-runs).
            try {
                Crypt::decryptString($plain);

                return;
            } catch (\Throwable $e) {
                // plaintext — encrypt it
            }

            DB::table('customers')->where('id', $row->id)->update([
                'cnic' => Crypt::encryptString($plain),
                'cnic_index' => Customer::blindIndex($plain),
            ]);
        });
    }

    public function down(): void
    {
        // Decrypt back to plaintext where possible, then drop the index column.
        DB::table('customers')->whereNotNull('cnic')->orderBy('id')->each(function ($row) {
            try {
                DB::table('customers')->where('id', $row->id)->update([
                    'cnic' => Crypt::decryptString($row->cnic),
                ]);
            } catch (\Throwable $e) {
                // already plaintext
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('cnic_index');
            $table->string('cnic')->nullable()->change();
        });
    }
};
