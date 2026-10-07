<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('gst_rate', 5, 2)->default(16.00);
            $table->boolean('system_locked')->default(false);
            $table->string('company_name')->default('Zircon Marquee');
            $table->string('company_address')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();
            $table->string('currency', 10)->default('PKR');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
