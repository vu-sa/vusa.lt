<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lets an author hide the page's own title/last-updated header — useful when a
     * page opens directly on an authored hero/section block that already carries a
     * title, and the plain `<h1>` above it would just be a duplicate.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('show_title')->default(true)->after('show_table_of_contents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('show_title');
        });
    }
};
