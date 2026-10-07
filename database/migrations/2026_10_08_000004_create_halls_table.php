<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('halls', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();      // opal, sapphire, full_marquee
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_composite')->default(false); // true for full marquee
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
