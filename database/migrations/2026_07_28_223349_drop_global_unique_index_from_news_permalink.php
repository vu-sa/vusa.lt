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
        Schema::table('news', function (Blueprint $table) {
            // The composite UNIQUE(permalink, tenant_id) remains. Dropping the
            // global single-column unique index allows the same permalink to exist
            // on different tenants (e.g. tf.vusa.lt/projektai and www.vusa.lt/projektai).
            $table->dropUnique('permalink');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally empty: re-adding a global unique index after cross-tenant
     * duplicates may have been created would fail. Existing migrations in this
     * project follow the same pattern for unique-index removals.
     */
    public function down(): void
    {
        //
    }
};
