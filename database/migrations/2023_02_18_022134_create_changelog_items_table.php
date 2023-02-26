<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('changelog_items', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            // datetime
            $table->dateTime('date');
            // description
            $table->json('description');
            $table->foreignUlid('permission_id')->nullable()->constrained('permissions')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            // check changelog get to know date
            $table->dateTime('last_changelog_check')->nullable()->after('last_action');
        });

        Artisan::call('db:seed', [
            '--class' => 'ChangelogItemPermissionSeeder',
            '--force' => 'true',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('changelog_items');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_changelog_check');
        });
    }
};
