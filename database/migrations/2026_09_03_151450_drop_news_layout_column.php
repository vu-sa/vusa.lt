<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The public redesign has one news article treatment, so the four-way `layout` choice
 * (`modern`/`classic`/`immersive`/`headline`) no longer selects anything — 1189 of 1191 articles
 * were on `modern` regardless. Leaving the column would keep an author-facing control in
 * NewsForm that changes nothing.
 *
 * `pages.layout` is deliberately untouched: ContentPage still offers default/wide/focused, and
 * they still mean different things.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('layout');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('layout', 20)->default('modern')->after('highlights');
        });
    }
};
