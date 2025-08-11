<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove doing-related permissions
        Permission::where('name', 'like', 'doings.%')->delete();

        // Drop foreign key constraints first
        if (Schema::hasTable('doables')) {
            Schema::table('doables', function (Blueprint $table) {
                $table->dropForeign(['doing_id']);
            });
        }

        if (Schema::hasTable('doing_user')) {
            Schema::table('doing_user', function (Blueprint $table) {
                $table->dropForeign(['doing_id']);
                $table->dropForeign(['user_id']);
            });
        }

        // Drop tables
        Schema::dropIfExists('doables');
        Schema::dropIfExists('doing_user');
        Schema::dropIfExists('doings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as we're removing data
        // If you need to restore, you'll need to recreate from backup
    }
};
