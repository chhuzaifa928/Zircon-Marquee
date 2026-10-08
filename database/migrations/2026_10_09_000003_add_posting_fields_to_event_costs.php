<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields that let an event cost post (and reverse) its accounting voucher and,
 * for inventory costs, its stock-out movement (SRS §10, posting policy #3/#6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_costs', function (Blueprint $table) {
            $table->decimal('quantity', 14, 2)->nullable()->after('cost_type');          // inventory qty consumed
            $table->foreignId('expense_account_id')->nullable()->after('amount')->constrained('accounts')->nullOnDelete(); // debit head (vendor/misc)
            $table->foreignId('paid_from_account_id')->nullable()->after('expense_account_id')->constrained('accounts')->nullOnDelete(); // credit cash/bank (misc)
            $table->foreignId('voucher_id')->nullable()->after('paid_from_account_id')->constrained('vouchers')->nullOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->after('voucher_id')->constrained('stock_movements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_costs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_account_id');
            $table->dropConstrainedForeignId('paid_from_account_id');
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropConstrainedForeignId('stock_movement_id');
            $table->dropColumn('quantity');
        });
    }
};
