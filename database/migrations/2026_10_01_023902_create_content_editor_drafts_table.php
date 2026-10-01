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
        Schema::create('content_editor_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 8);
            $table->string('identity', 48);
            $table->json('snapshot');
            $table->unsignedInteger('revision')->default(1);
            $table->unique(['user_id', 'kind', 'identity'], 'content_editor_draft_identity');
            $table->index('updated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_editor_drafts');
    }
};
