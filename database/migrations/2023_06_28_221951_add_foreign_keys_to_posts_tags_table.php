<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('posts_tags', function (Blueprint $table) {
            $table->foreign(['page_id'])->references(['id'])->on('pages');
            $table->foreign(['news_id'])->references(['id'])->on('news');
            $table->foreign(['tag_id'])->references(['id'])->on('tags');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('posts_tags', function (Blueprint $table) {
            $table->dropForeign('posts_tags_page_id_foreign');
            $table->dropForeign('posts_tags_news_id_foreign');
            $table->dropForeign('posts_tags_tag_id_foreign');
        });
    }
};
