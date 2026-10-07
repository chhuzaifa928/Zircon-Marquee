<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_no')->unique();          // gapless sequence
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('hall_id')->constrained('halls');

            $table->date('event_date');
            $table->date('booking_date');                    // date the booking was taken
            $table->string('slot');                          // lunch, dinner
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('event_type')->nullable();        // wedding, mehndi, ...
            $table->unsignedInteger('guests')->default(0);

            // Per-head pricing
            $table->decimal('rack_rate', 10, 2)->default(0);
            $table->decimal('discounted_rate', 10, 2)->default(0);

            // Lifecycle
            $table->string('status')->default('tentative');  // tentative, booked, paid, cancelled
            $table->boolean('is_locked')->default(false);
            $table->decimal('gst_rate', 5, 2)->default(16.00); // snapshot at creation

            // Snapshot totals (maintained by BookingTotals service)
            $table->decimal('headcharge_total', 14, 2)->default(0);
            $table->decimal('charges_total', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('gst_amount', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('advance_total', 14, 2)->default(0);
            $table->decimal('due', 14, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();   // salesperson
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete(); // accounts
            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['hall_id', 'event_date', 'slot']); // availability lookups (NOT unique)
            $table->index('status');
            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
