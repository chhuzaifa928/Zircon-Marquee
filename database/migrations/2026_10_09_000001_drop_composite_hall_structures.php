<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove the composite "Full Marquee" hall entirely (owner change, 2026-10-09).
 *
 * There are only two independent halls, Opal and Sapphire. Availability blocks a
 * confirmed booking only on the SAME hall + date + slot — no cross-hall
 * blocking — so the composite hall, the hall_components pivot and the
 * is_composite flag are all dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hall_components');

        if (Schema::hasColumn('halls', 'is_composite')) {
            Schema::table('halls', function (Blueprint $table) {
                $table->dropColumn('is_composite');
            });
        }

        // Remove the Full Marquee hall if no booking references it.
        $full = DB::table('halls')->where('slug', 'full_marquee')->first();

        if ($full && ! DB::table('bookings')->where('hall_id', $full->id)->exists()) {
            DB::table('halls')->where('id', $full->id)->delete();
        }
    }

    public function down(): void
    {
        // The composite feature was removed by design; restore only the column
        // so the migration is reversible (the pivot and data are not recreated).
        if (! Schema::hasColumn('halls', 'is_composite')) {
            Schema::table('halls', function (Blueprint $table) {
                $table->boolean('is_composite')->default(false)->after('capacity');
            });
        }
    }
};
