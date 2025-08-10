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
        // Remove matter-related permissions
        Permission::where('name', 'like', 'matters.%')->delete();

        // Drop foreign key constraints first to avoid constraint violations
        // Only proceed if both table and column exist
        if (Schema::hasTable('agenda_items') && Schema::hasColumn('agenda_items', 'matter_id')) {
            Schema::table('agenda_items', function (Blueprint $table) {
                $table->dropForeign(['matter_id']);
                $table->dropColumn('matter_id');
            });
        }

        // Drop pivot tables
        Schema::dropIfExists('goal_matter');
        Schema::dropIfExists('institutions_matters');

        // Drop main matters table
        Schema::dropIfExists('matters');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate matters table
        Schema::create('matters', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Recreate pivot tables
        Schema::create('goal_matter', function (Blueprint $table) {
            $table->char('goal_id', 26);
            $table->char('matter_id', 26)->index('goal_matter_matter_id_foreign');
            $table->timestamps();
            $table->primary(['goal_id', 'matter_id']);

            $table->foreign(['goal_id'])->references(['id'])->on('goals')->onDelete('cascade');
            $table->foreign(['matter_id'])->references(['id'])->on('matters')->onDelete('cascade');
        });

        Schema::create('institutions_matters', function (Blueprint $table) {
            $table->char('institution_id', 26);
            $table->char('matter_id', 26)->index('institutions_matters_matter_id_foreign');
            $table->timestamps();
            $table->primary(['institution_id', 'matter_id']);

            $table->foreign(['institution_id'])->references(['id'])->on('institutions')->onDelete('cascade');
            $table->foreign(['matter_id'])->references(['id'])->on('matters')->onDelete('cascade');
        });

        // Re-add matter_id column to agenda_items
        if (Schema::hasTable('agenda_items')) {
            Schema::table('agenda_items', function (Blueprint $table) {
                $table->char('matter_id', 26)->nullable()->index('agenda_items_matter_id_foreign');
                $table->foreign(['matter_id'])->references(['id'])->on('matters');
            });
        }
    }
};
