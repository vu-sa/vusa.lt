<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lets an author hide the breadcrumb trail on a news article — e.g. when the
     * chosen layout (immersive / headline) already leads with a full-bleed title
     * and the breadcrumb row above it would only clutter the opening visual.
     */
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->boolean('show_breadcrumbs')->default(true)->after('layout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('show_breadcrumbs');
        });
    }
};
