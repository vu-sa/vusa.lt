<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->string('title', 160)->nullable()->after('kind');
            $table->char('author_dutiable_id', 26)->nullable();
            $table->foreign('author_dutiable_id')->references('id')->on('dutiables')->nullOnDelete();
            $table->index(['commentable_type', 'commentable_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex(['commentable_type', 'commentable_id', 'kind']);
            $table->dropForeign(['author_dutiable_id']);
            $table->dropColumn(['title', 'author_dutiable_id']);
        });
    }
};
