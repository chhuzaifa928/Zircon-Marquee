<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Itemised extra charges: cold_drinks, mineral_water, ac_heating,
        // hall_charges, decor, dj, other_charges.
        Schema::create('booking_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('charge_type');
            $table->string('description')->nullable();
            $table->decimal('quantity', 14, 2)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_charges');
    }
};
