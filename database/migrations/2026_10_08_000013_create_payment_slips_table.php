<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_slips', function (Blueprint $table) {
            $table->id();
            $table->string('slip_no')->unique();          // gapless sequence
            $table->foreignId('booking_id')->constrained('bookings');
            $table->foreignId('account_id')->constrained('accounts'); // cash/bank received into
            $table->date('date');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('method')->nullable();         // cash, bank_transfer, cheque, card
            $table->string('reference')->nullable();
            $table->text('remark')->nullable();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_slips');
    }
};
