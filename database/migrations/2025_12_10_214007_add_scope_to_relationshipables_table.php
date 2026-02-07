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
            // Scope defines how type-based relationships resolve to institutions:
            // - 'within-tenant': Matches institutions within the same tenant (default, current behavior)
            // - 'cross-tenant': Type in pagrindinis tenant relates to institutions in all padalinys-type tenants
            $table->string('scope')->default('within-tenant')->after('related_model_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relationshipables', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};
