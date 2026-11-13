<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_item_sources', function (Blueprint $table): void {
            $table->timestamp('checked_at')->nullable();
            $table->unsignedSmallInteger('check_status')->nullable();
            $table->unsignedTinyInteger('check_failures')->default(0);
            $table->string('content_version', 255)->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('thumbnail_version', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_item_sources', function (Blueprint $table): void {
            $table->dropColumn(['checked_at', 'check_status', 'check_failures', 'content_version', 'thumbnail_path', 'thumbnail_version']);
        });
    }
};
