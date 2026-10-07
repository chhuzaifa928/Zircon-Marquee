<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 14, 2)->nullable();
            $table->string('condition')->nullable();   // new, good, fair, poor
            $table->string('status')->default('available'); // available, in_use, retired
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
