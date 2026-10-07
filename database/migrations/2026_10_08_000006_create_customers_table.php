<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();      // gapless sequence, e.g. CUST-0001
            $table->string('name');
            $table->string('phone')->nullable();   // plain/searchable for now
            $table->string('cnic')->nullable();    // plain/searchable for now
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
