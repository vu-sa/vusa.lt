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
        Schema::table('pages', function (Blueprint $table): void {
            // Permalinks stay flat — this drives breadcrumbs, section navigation and
            // child listings only. Same-lang/same-tenant/no-cycle/max-3-levels is
            // enforced in ValidPageParent, not here.
            //
            // `pages.id` is a legacy `INT UNSIGNED` (created via `increments()`, not
            // `id()`) — `foreignId()` would emit a `BIGINT` and MySQL refuses a
            // self-referencing FK across mismatched column types (errno 150).
            $table->unsignedInteger('parent_id')->nullable()->after('category_id');
            $table->foreign('parent_id')->references('id')->on('pages')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0)->after('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'sort_order']);
        });
    }
};
