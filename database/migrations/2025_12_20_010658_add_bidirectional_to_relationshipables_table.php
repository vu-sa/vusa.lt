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
        Schema::table('relationshipables', function (Blueprint $table) {
            // When true, both source and target can see each other's data (meetings, etc.)
            // When false (default), only source can see target's data
            $table->boolean('bidirectional')->default(false)->after('scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relationshipables', function (Blueprint $table) {
            $table->dropColumn('bidirectional');
        });
    }
};
