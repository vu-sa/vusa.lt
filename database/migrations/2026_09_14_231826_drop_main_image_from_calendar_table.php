<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drops the legacy pre-media-library `main_image` URL column. Superseded by the Spatie
     * Media `main_image` collection; unused by any write path and empty on every row in
     * production, confirmed before this migration was written.
     */
    public function up(): void
    {
        Schema::table('calendar', function (Blueprint $table) {
            $table->dropColumn('main_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar', function (Blueprint $table) {
            $table->text('main_image')->nullable()->after('video_url');
        });
    }
};
