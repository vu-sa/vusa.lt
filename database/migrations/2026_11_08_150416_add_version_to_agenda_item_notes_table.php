<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each save bumps the version, so a save based on an older snapshot is refused
     * instead of overwriting notes it never saw.
     */
    public function up(): void
    {
        Schema::table('agenda_item_notes', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(0)->after('notes_html');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_item_notes', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
