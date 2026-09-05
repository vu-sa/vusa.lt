<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar', function (Blueprint $table): void {
            $table->string('main_image_focal_point', 20)->nullable()->after('main_image');
        });
    }

    public function down(): void
    {
        Schema::table('calendar', function (Blueprint $table): void {
            $table->dropColumn('main_image_focal_point');
        });
    }
};
