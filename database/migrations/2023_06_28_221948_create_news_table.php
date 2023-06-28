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
        Schema::create('news', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 200);
            $table->unsignedInteger('category_id')->nullable()->index('news_category_id_foreign');
            $table->string('permalink', 150)->nullable()->unique('permalink');
            $table->mediumText('short');
            $table->mediumText('text');
            $table->string('lang', 2)->default('lt');
            $table->unsignedInteger('other_lang_id')->nullable()->index();
            $table->string('image', 200)->nullable()->default('058543019bc51198ea1dc255580d215be99f2297.jpeg');
            $table->string('image_author', 200)->nullable()->default('VU SA archyvo nuotrauka');
            $table->boolean('important')->default(false);
            $table->unsignedInteger('padalinys_id')->index('news_padalinys_id_foreign');
            $table->timestamp('publish_time')->nullable();
            $table->mediumText('main_points')->nullable();
            $table->string('read_more', 100)->nullable();
            $table->boolean('draft')->nullable()->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->unique(['permalink', 'padalinys_id'], 'news_permalink_role_id_unique');
            $table->unique(['other_lang_id']);
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
};
