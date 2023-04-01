<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // use existing calendar table as events
        Schema::create('event_initiative', function (Blueprint $table) {
            $table->unsignedInteger('event_id');
            $table->foreign('event_id')->references('id')->on('calendar');
            $table->foreignUlid('initiative_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_initiative');
    }
};
