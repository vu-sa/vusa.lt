<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Workspaces are user-created meeting-preparation spaces. Attaching an
     * institution grants its active representatives automatic access; explicit
     * membership (workspace_user) covers everyone else.
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->char('institution_id', 26)->nullable();
            $table->char('created_by', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('institution_id')->references('id')->on('institutions')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('workspace_user', function (Blueprint $table) {
            $table->id();
            $table->char('workspace_id', 26);
            $table->char('user_id', 26);
            $table->string('role')->default('member');
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id']);
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_user');
        Schema::dropIfExists('workspaces');
    }
};
