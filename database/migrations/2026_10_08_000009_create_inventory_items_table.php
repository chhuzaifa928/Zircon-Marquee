<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->nullable();    // unit of measure: pcs, kg, ltr...
            $table->decimal('qty_on_hand', 14, 2)->default(0);
            $table->decimal('reorder_level', 14, 2)->default(0);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
