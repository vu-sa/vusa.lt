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
        Schema::table('padaliniai', function (Blueprint $table) {
            // remove columns: "en"
            $table->dropColumn('en');
            // add column: primary_institution_id (ulid)
            $table->string('primary_institution_id', 26)->nullable();
            // add foreign key: primary_institution_id -> institutions(id)
            $table->foreign('primary_institution_id')->references('id')->on('institutions');
        });

        Schema::rename('padaliniai', 'tenants');

        Schema::table('banners', function (Blueprint $table) {
            // rename column: padalinys_id -> tenant_id
            $table->renameColumn('padalinys_id', 'tenant_id');
        });

        Schema::table('calendar', function (Blueprint $table) {
            // rename column: padalinys_id -> tenant_id
            $table->renameColumn('padalinys_id', 'tenant_id');
        });

        Schema::table('goals', function (Blueprint $table) {
            // rename column: padalinys_id -> tenant_id
            $table->renameColumn('padalinys_id', 'tenant_id');
        });

        Schema::table('institutions', function (Blueprint $table) {
            // rename column: padalinys_id -> tenant_id
            $table->renameColumn('padalinys_id', 'tenant_id');
        });

        Schema::table('main_page', function (Blueprint $table) {
            // rename column: padalinys_id -> tenant_id
            $table->renameColumn('padalinys_id', 'tenant_id');
        });

        Schema::table('news', function (Blueprint $table) {
            // rename column: padalinys_id -> tenant_id
            $table->renameColumn('padalinys_id', 'tenant_id');
        });

        Schema::table('pages', function (Blueprint $table) {
            // rename column: padalinys_id -> tenant_id
            $table->renameColumn('padalinys_id', 'tenant_id');
        });

        Schema::table('resources', function (Blueprint $table) {
            // rename column: padalinys_id -> tenant_id
            $table->renameColumn('padalinys_id', 'tenant_id');
        });

        Schema::table('navigation', function (Blueprint $table) {
            // drop column
            $table->dropForeign('navigation_padalinys_id_foreign');
            $table->dropColumn('padalinys_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('padaliniai', function (Blueprint $table) {
            // add columns: "en"
            $table->string('en')->nullable();
            // remove column: primary_institution_id
            $table->dropColumn('primary_institution_id');
            // drop foreign key: primary_institution_id -> institutions(id)
            $table->dropForeign(['primary_institution_id']);
        });

        Schema::rename('tenants', 'padaliniai');

        Schema::table('banners', function (Blueprint $table) {
            // rename column: tenant_id -> padalinys_id
            $table->renameColumn('tenant_id', 'padalinys_id');
        });

        Schema::table('calendar', function (Blueprint $table) {
            // rename column: tenant_id -> padalinys_id
            $table->renameColumn('tenant_id', 'padalinys_id');
        });

        Schema::table('duties', function (Blueprint $table) {
            // rename column: tenant_id -> padalinys_id
            $table->renameColumn('tenant_id', 'padalinys_id');
        });

        Schema::table('institutions', function (Blueprint $table) {
            // rename column: tenant_id -> padalinys_id
            $table->renameColumn('tenant_id', 'padalinys_id');
        });

        Schema::table('news', function (Blueprint $table) {
            // rename column: tenant_id -> padalinys_id
            $table->renameColumn('tenant_id', 'padalinys_id');
        });

        Schema::table('pages', function (Blueprint $table) {
            // rename column: tenant_id -> padalinys_id
            $table->renameColumn('tenant_id', 'padalinys_id');
        });

        Schema::table('resources', function (Blueprint $table) {
            // rename column: tenant_id -> padalinys_id
            $table->renameColumn('tenant_id', 'padalinys_id');
        });

        Schema::table('users', function (Blueprint $table) {
            // rename column: tenant_id -> padalinys_id
            $table->renameColumn('tenant_id', 'padalinys_id');
        });

        Schema::table('navigation', function (Blueprint $table) {
            // add column
            $table->unsignedBigInteger('padalinys_id')->nullable();
        });
    }
};
