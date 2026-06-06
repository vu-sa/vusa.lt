<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-tap emoji reactions on comments. A user may add each distinct emoji
     * once per comment (the unique index enforces a toggle, not a tally bump).
     */
    public function up(): void
    {
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('comment_id', 26);
            $table->char('user_id', 26);
            $table->string('emoji');
            $table->timestamps();

            $table->unique(['comment_id', 'user_id', 'emoji']);
            $table->index(['comment_id', 'emoji']);

            $table->foreign('comment_id')->references('id')->on('comments')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
    }
};
