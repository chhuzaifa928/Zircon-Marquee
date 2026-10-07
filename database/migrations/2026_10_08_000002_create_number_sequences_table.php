<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();   // e.g. booking_no, slip_no, voucher_no:RV, customer_code
            $table->unsignedBigInteger('next')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
