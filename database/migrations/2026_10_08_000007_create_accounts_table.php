<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chart of accounts. type: cash, bank, staff, income, expense, supplier.
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('name');
            $table->string('type');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
