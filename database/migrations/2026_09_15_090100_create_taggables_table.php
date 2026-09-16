<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table): void {
            $table->id();
            // tags.id is `int unsigned` (legacy `increments()`), not bigint — foreignId()'s
            // default unsignedBigInteger would mismatch and MySQL refuses the FK.
            $table->unsignedInteger('tag_id');
            $table->string('taggable_type');
            $table->unsignedBigInteger('taggable_id');
            $table->timestamps();

            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
            $table->unique(['tag_id', 'taggable_type', 'taggable_id']);
            $table->index(['taggable_type', 'taggable_id']);
        });

        // `posts_tags` is a two-slot pseudo-polymorphic pivot (page_id/news_id, page_id always
        // NULL in practice — pages were never taggable). Every row here is news.
        $now = now();
        DB::table('posts_tags')
            ->whereNotNull('news_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($now): void {
                DB::table('taggables')->insert($rows->map(fn ($row) => [
                    'tag_id' => $row->tag_id,
                    'taggable_type' => 'news',
                    'taggable_id' => $row->news_id,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->created_at ?? $now,
                ])->all());
            });

        Schema::dropIfExists('posts_tags');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('posts_tags', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('page_id')->nullable();
            $table->unsignedInteger('tag_id')->index('posts_tags_tag_id_foreign');
            $table->unsignedInteger('news_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['page_id', 'tag_id']);
            $table->unique(['news_id', 'tag_id']);
        });

        Schema::table('posts_tags', function (Blueprint $table): void {
            $table->foreign(['tag_id'])->references(['id'])->on('tags');
            $table->foreign(['page_id'])->references(['id'])->on('pages');
            $table->foreign(['news_id'])->references(['id'])->on('news');
        });

        DB::table('taggables')
            ->where('taggable_type', 'news')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                DB::table('posts_tags')->insert($rows->map(fn ($row) => [
                    'tag_id' => $row->tag_id,
                    'news_id' => $row->taggable_id,
                    'created_at' => $row->created_at,
                ])->all());
            });

        Schema::dropIfExists('taggables');
    }
};
