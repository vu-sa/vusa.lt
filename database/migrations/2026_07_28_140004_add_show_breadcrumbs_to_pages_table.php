<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lets an author hide the breadcrumb trail on a page — useful when the page
     * opens on a hero/section block whose own title already orients the visitor,
     * and the breadcrumb row above it would only add visual noise.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('show_breadcrumbs')->default(true)->after('show_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('show_breadcrumbs');
        });
    }
};
