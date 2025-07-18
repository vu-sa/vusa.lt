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
        Schema::dropIfExists('pulse_values');
        Schema::dropIfExists('pulse_entries');
        Schema::dropIfExists('pulse_aggregates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These tables were created by Laravel Pulse package
        // If needed, they can be recreated by reinstalling the package
        // and running the original pulse migration
    }
};
