<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->json('title')->nullable()->after('name');
            $table->json('description')->nullable()->after('title');
            $table->string('purpose')->nullable()->unique()->after('description');
        });

        Schema::create('responsibility_grants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('role_id', 26);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            // Duties and institutions have ULID keys, duty types integers, so the ids are strings.
            $table->string('grantee_type');
            $table->string('grantee_id', 26);
            $table->string('scope_type')->nullable();
            $table->string('scope_id', 26)->nullable();
            $table->timestamps();

            $table->unique(['role_id', 'grantee_type', 'grantee_id', 'scope_type', 'scope_id'], 'responsibility_grants_unique');
            $table->index(['grantee_type', 'grantee_id'], 'responsibility_grants_grantee');
            $table->index(['scope_type', 'scope_id'], 'responsibility_grants_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsibility_grants');

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['purpose']);
            $table->dropColumn(['title', 'description', 'purpose']);
        });
    }
};
