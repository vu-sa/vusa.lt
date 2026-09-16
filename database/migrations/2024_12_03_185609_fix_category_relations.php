<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reads `categories` through the query builder, not the `Category` model — the model
     * was removed once the taxonomy redesign retired the table (see
     * `2026_09_15_130000_retire_categories_table.php`), and a migration replayed from
     * scratch (`migrate:fresh`) must not depend on application classes that may not exist
     * by the time it runs.
     */
    public function up(): void
    {
        $calendars = DB::table('calendar')->get(['id', 'category']);
        $categories = DB::table('categories')->get(['id', 'alias']);

        Schema::table('calendar', function (Blueprint $table) {
            $table->dropForeign(['category']);
            $table->renameColumn('category', 'category_id');
        });

        foreach ($calendars as $calendar) {
            $category = $categories->firstWhere('alias', $calendar->category);
            if ($category) {
                DB::table('calendar')->where('id', $calendar->id)->update(['category_id' => $category->id]);
            }
        }

        Schema::table('calendar', function (Blueprint $table) {
            $table->unsignedInteger('category_id')->nullable()->change();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
