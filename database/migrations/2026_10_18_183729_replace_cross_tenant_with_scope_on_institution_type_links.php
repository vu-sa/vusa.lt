<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A boolean could not express "between all units", so the type link's reach becomes a scope.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_type_links', function (Blueprint $table): void {
            $table->string('scope')->default('same_tenant')->after('mutual');
        });

        DB::table('institution_type_links')->where('cross_tenant', true)->update(['scope' => 'main_to_units']);

        // The new index first: MySQL keeps the source FK's index while the old one goes.
        Schema::table('institution_type_links', function (Blueprint $table): void {
            $table->unique(['source_type_id', 'target_type_id', 'scope'], 'institution_type_links_scope_unique');
        });

        Schema::table('institution_type_links', function (Blueprint $table): void {
            $table->dropUnique('institution_type_links_pair_unique');
            $table->dropColumn('cross_tenant');
        });
    }

    public function down(): void
    {
        Schema::table('institution_type_links', function (Blueprint $table): void {
            $table->boolean('cross_tenant')->default(false)->after('mutual');
        });

        // all_tenants has no boolean equivalent; those links are dropped.
        DB::table('institution_type_links')->where('scope', 'all_tenants')->delete();
        DB::table('institution_type_links')->where('scope', 'main_to_units')->update(['cross_tenant' => true]);

        Schema::table('institution_type_links', function (Blueprint $table): void {
            $table->unique(['source_type_id', 'target_type_id', 'cross_tenant'], 'institution_type_links_pair_unique');
        });

        Schema::table('institution_type_links', function (Blueprint $table): void {
            $table->dropUnique('institution_type_links_scope_unique');
            $table->dropColumn('scope');
        });
    }
};
