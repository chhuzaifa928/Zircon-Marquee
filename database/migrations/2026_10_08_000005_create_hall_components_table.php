<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Maps a composite hall (e.g. full_marquee) to its component halls
        // (opal, sapphire). Booking any member blocks the others for a slot.
        Schema::create('hall_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->foreignId('component_hall_id')->constrained('halls')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['hall_id', 'component_hall_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hall_components');
    }
};
