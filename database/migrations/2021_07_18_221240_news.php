<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class News extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_lt', 200)->nullable()->default('NULL');
            $table->smallInteger('cat');
            $table->string('permalink', 125)->unique('permalink')->nullable()->default(NULL);
            $table->string('permalink_lt', 125)->nullable()->default(NULL);
            $table->mediumText('short');
            $table->mediumText('text');
            $table->string('lang', 2)->default('lt');
            $table->string('image', 200)->nullable()->default('058543019bc51198ea1dc255580d215be99f2297.jpeg');
            $table->string('imageAuthor', 200)->nullable()->default('VU SA archyvo nuotrauka');
            $table->tinyInteger('important')->default(0);
            $table->smallInteger('editor');
            $table->timestamp('editorTime')->useCurrent()->useCurrentOnUpdate();
            $table->smallInteger('publisher')->nullable()->default(NULL);
            $table->timestamp('publish_time')->nullable()->default(NULL);
            $table->string('source', 100)->nullable()->default('VU SA');
            $table->mediumText('mainPoints')->nullable()->default(NULL);
            $table->string('tags', 100)->nullable()->default(NULL);
            $table->string('readMore', 100)->nullable()->default(NULL);
            $table->tinyInteger('draft')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news');
    }
}
