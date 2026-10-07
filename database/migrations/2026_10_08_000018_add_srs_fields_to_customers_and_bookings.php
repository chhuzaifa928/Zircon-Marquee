<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('care_of')->nullable()->after('name'); // SRS §5.2
        });

        Schema::table('bookings', function (Blueprint $table) {
            // Free-text notes that print on the Event Booking Sheet (SRS §7.9, §14.1)
            $table->text('arrangements')->nullable()->after('remarks');
            $table->text('bulletin_board')->nullable()->after('arrangements');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('care_of');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['arrangements', 'bulletin_board']);
        });
    }
};
