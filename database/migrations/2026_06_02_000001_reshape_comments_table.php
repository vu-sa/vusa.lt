<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reshape the `comments` table for the new discussion framework. Existing
     * comment data is disposable, so this is a destructive drop-and-recreate
     * rather than an incremental column migration.
     *
     * Adds proper threading (`parent_id` + `thread_root_id`), a `kind` seam for
     * future polls, denormalised `mentioned_user_ids`, resolve tracking, and an
     * `edited_at` marker. Drops the legacy `decision` column (decisions moved to
     * the approvals table — see 2026_01_15_160000).
     */
    public function up(): void
    {
        Schema::dropIfExists('comments');

        Schema::create('comments', function (Blueprint $table) {
            $table->char('id', 26)->primary();

            // Threading: direct reply parent + denormalised thread root for
            // single-query thread fetches and flat-load + client-side nesting.
            $table->char('parent_id', 26)->nullable();
            $table->char('thread_root_id', 26)->nullable();

            // Polymorphic owner.
            $table->string('commentable_type');
            $table->char('commentable_id', 36);

            $table->char('user_id', 26);

            // 'comment' (default) | 'poll' (future). See App\Enums\CommentKind.
            $table->string('kind')->default('comment');

            // HTML body (renamed from the legacy `comment` column).
            $table->text('body');

            // Poll seam + general extensibility (options, deadline, settings…).
            $table->json('metadata')->nullable();

            // User ULIDs extracted from @mention nodes on save; drives mention
            // notifications and the "mentions me" feed via whereJsonContains.
            $table->json('mentioned_user_ids')->nullable();

            // Resolve (closure) tracking — applies to thread roots.
            $table->timestamp('resolved_at')->nullable();
            $table->char('resolved_by', 26)->nullable();

            // Set when the body changes after creation (distinct from updated_at,
            // which $touches noise also bumps).
            $table->timestamp('edited_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['commentable_type', 'commentable_id', 'created_at']);
            $table->index('thread_root_id');
            $table->index('parent_id');
            $table->index('user_id');
            $table->index('resolved_at');

            $table->foreign('parent_id')->references('id')->on('comments')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse to the legacy comments shape (forward-only in practice).
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');

        Schema::create('comments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('parent_id', 26)->nullable();
            $table->text('comment');
            $table->string('decision')->nullable();
            $table->char('user_id', 26)->index('comments_user_id_foreign');
            $table->string('commentable_type');
            $table->char('commentable_id', 36);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->softDeletes();

            $table->index(['commentable_type', 'commentable_id']);
        });
    }
};
