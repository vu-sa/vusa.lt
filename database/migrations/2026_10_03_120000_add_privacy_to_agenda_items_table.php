<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_items', function (Blueprint $table): void {
            $table->boolean('is_private')->default(false);
            $table->json('public_title')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agenda_items', function (Blueprint $table): void {
            $table->dropColumn(['is_private', 'public_title']);
        });
    }
};
