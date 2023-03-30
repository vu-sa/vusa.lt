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
        Schema::table('dutiables', function (Blueprint $table) {
            // remove the old primary key
            $table->dropPrimary(['dutiable_id', 'dutiable_type', 'duty_id']);
            $table->primary(['dutiable_id', 'dutiable_type', 'duty_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dutiables', function (Blueprint $table) {
            // remove the new primary key
            $table->dropPrimary(['dutiable_id', 'dutiable_type', 'duty_id', 'start_date']);
            $table->primary(['dutiable_id', 'dutiable_type', 'duty_id']);
        });
    }
};
