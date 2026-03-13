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
        Schema::table('planning_processes', function (Blueprint $table) {
            $table->unsignedBigInteger('tip_approved_media_id')->nullable()->after('tip_approved_by');
            $table->unsignedBigInteger('mvp_approved_media_id')->nullable()->after('mvp_approved_by');

            $table->foreign('tip_approved_media_id')->references('id')->on('media')->nullOnDelete();
            $table->foreign('mvp_approved_media_id')->references('id')->on('media')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planning_processes', function (Blueprint $table) {
            $table->dropForeign(['tip_approved_media_id']);
            $table->dropForeign(['mvp_approved_media_id']);
            $table->dropColumn(['tip_approved_media_id', 'mvp_approved_media_id']);
        });
    }
};
