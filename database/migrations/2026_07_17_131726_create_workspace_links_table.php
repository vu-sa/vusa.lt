<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Polymorphic references from a workspace to the records being prepared for
     * (meetings, agenda items, problems, institutions). Allowed morph types are
     * constrained by the WorkspaceLinkables allowlist at the application layer.
     */
    public function up(): void
    {
        Schema::create('workspace_links', function (Blueprint $table) {
            $table->id();
            $table->char('workspace_id', 26);
            $table->string('linkable_type');
            $table->string('linkable_id', 26);
            $table->char('added_by', 26)->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'linkable_type', 'linkable_id']);
            $table->index(['linkable_type', 'linkable_id']);
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('added_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_links');
    }
};
