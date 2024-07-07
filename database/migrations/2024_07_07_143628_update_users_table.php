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
        Schema::table('users', function (Blueprint $table) {
            $table->json('pronouns')->nullable()->after('name');
            $table->boolean('show_pronouns')->default(false)->after('pronouns');
            $table->dropColumn('google_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pronouns');
            $table->dropColumn('show_pronouns');
            $table->string('google_token')->nullable()->after('name');
        });
    }
};
