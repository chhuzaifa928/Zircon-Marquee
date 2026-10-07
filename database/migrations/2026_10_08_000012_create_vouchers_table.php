<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Double-entry vouchers. type: RV (receipt), EV (expense), PV (payment).
        // Posting rules intentionally NOT wired yet — structure only.
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();      // gapless sequence, prefixed per type
            $table->string('type');                      // RV, EV, PV
            $table->date('date');
            $table->string('category')->nullable();
            $table->text('remark')->nullable();

            $table->foreignId('debit_account_id')->constrained('accounts');
            $table->foreignId('credit_account_id')->constrained('accounts');
            $table->decimal('amount', 14, 2)->default(0);

            // Polymorphic link back to the originating record (booking/slip/event_cost)
            $table->nullableMorphs('source');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
