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
        Schema::table('navigation', function (Blueprint $table) {
            $table->json('extra_attributes')->nullable()->after('is_active')->comment('column, icon, image, style');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('navigation', function (Blueprint $table) {
            $table->dropColumn('extra_attributes');
        });
    }
};
