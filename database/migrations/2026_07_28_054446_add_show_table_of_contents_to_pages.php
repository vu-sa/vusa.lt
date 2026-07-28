<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lets an author disable the `default` layout's sidebar table of contents —
     * needed once a page can also carry full-width rich-content blocks, which the
     * sidebar clips down to a narrower measure while it's present.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('show_table_of_contents')->default(true)->after('layout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('show_table_of_contents');
        });
    }
};
