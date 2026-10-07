<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // type: in, out, adjustment.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('type');
            $table->decimal('quantity', 14, 2)->default(0);
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->date('date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
