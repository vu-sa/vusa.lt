<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Votes cast on a poll comment (a comment with kind=poll). `option_id`
     * references an option in the poll's metadata. The unique index keys on the
     * option too, so it is forward-compatible with multiple-choice polls; single
     * choice is enforced in the application layer.
     */
    public function up(): void
    {
        Schema::create('comment_poll_votes', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('comment_id', 26);
            $table->char('user_id', 26);
            $table->string('option_id');
            $table->timestamps();

            $table->unique(['comment_id', 'user_id', 'option_id']);
            $table->index(['comment_id', 'option_id']);

            $table->foreign('comment_id')->references('id')->on('comments')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_poll_votes');
    }
};
