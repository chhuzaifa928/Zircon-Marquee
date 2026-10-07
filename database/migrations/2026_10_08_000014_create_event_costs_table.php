<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Accounts-only, entered only after the booking is fully paid.
        // cost_type: vendor, inventory, misc.
        Schema::create('event_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('cost_type');
            $table->string('description')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->date('date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_costs');
    }
};
