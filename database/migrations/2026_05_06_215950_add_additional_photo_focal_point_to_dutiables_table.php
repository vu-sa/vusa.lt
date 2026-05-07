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
            $table->string('additional_photo_focal_point', 20)->nullable()->after('additional_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dutiables', function (Blueprint $table) {
            $table->dropColumn('additional_photo_focal_point');
        });
    }
};
