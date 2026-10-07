<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decor_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('rate', 14, 2)->nullable();
            $table->string('status')->default('available'); // available, in_use, retired
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decor_items');
    }
};
