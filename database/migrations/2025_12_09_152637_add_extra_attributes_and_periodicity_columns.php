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
        Schema::table('types', function (Blueprint $table) {
            $table->json('extra_attributes')->nullable()->after('slug');
        });

        Schema::table('institutions', function (Blueprint $table) {
            // Override meeting periodicity from institution types. If null, inherits from assigned types (default: 30 days)
            $table->unsignedInteger('meeting_periodicity_days')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('types', function (Blueprint $table) {
            $table->dropColumn('extra_attributes');
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('meeting_periodicity_days');
        });
    }
};
