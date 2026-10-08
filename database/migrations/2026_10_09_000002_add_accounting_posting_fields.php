<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields that support auto-posting (Step 4b):
 *  - vouchers.batch_uid groups the paired vouchers of one Close-Event posting
 *    so the set can be found and reversed together.
 *  - bookings.event_closed_at marks revenue as recognised (posting #2) and
 *    gates event costing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->uuid('batch_uid')->nullable()->after('voucher_no')->index();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('event_closed_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('batch_uid');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('event_closed_at');
        });
    }
};
