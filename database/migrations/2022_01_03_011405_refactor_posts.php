<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefactorPosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('alias')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        $page_cats = DB::table('page_cats')->select('name')->get();

        foreach ($page_cats as $page_cat) {
            DB::table('categories')->insert([
                'name' => $page_cat->name,
            ]);
        }

        DB::table('categories')->where('name', '=', 'Akademinė informacija')->update(['alias' => 'red']);
        DB::table('categories')->where('name', '=', 'Socialinė informacija')->update(['alias' => 'yellow']);
        DB::table('categories')->where('name', '=', 'Kita informacija')->update(['alias' => 'grey']);

        Schema::dropIfExists('news_cats');
        Schema::dropIfExists('page_cats');

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('alias');
            $table->string('alias')->change();
        });

        DB::table('calendar')->where('category', '=', '')->delete();

        Schema::table('calendar', function (Blueprint $table) {
            $table->foreign('category')->references('alias')->on('categories');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->dropColumn('editor');
            $table->renameColumn('editorG', 'role_id');
            $table->renameColumn('mainInfo', 'aside');
            $table->renameColumn('category', 'category_id');
            $table->renameColumn('disabled', 'is_active');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->increments('id')->change();
        });

        Schema::create('posts_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('page_id')->nullable();
            $table->foreign('page_id')->references('id')->on('pages');
            $table->unsignedInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->unsignedInteger('news_id')->nullable();
            $table->foreign('news_id')->references('id')->on('news');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->after('created_at')->change();
        });

        DB::table('pages')->where('role_id', '=', 23)->delete();

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('readonly');
            $table->dropColumn('permalink_lt');
            $table->dropColumn('title_lt');
            $table->unsignedInteger('other_lang_id')->nullable()->index()->after('lang');
            $table->text('aside')->nullable()->after('text')->change();
            $table->unique(['permalink', 'role_id']);
            $table->unique(['other_lang_id']);
            $table->unsignedInteger('user_id')->nullable()->after('id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedInteger('role_id')->change();
            $table->foreign('role_id')->references('id')->on('roles');
            $table->unsignedInteger('category_id')->nullable()->change();
            $table->boolean('is_active')->default(true)->change();
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('editor');
            $table->renameColumn('publisher', 'role_id');
            $table->renameColumn('cat', 'category_id');
            $table->renameColumn('readMore', 'read_more');
            $table->renameColumn('mainPoints', 'main_points');
            $table->renameColumn('imageAuthor', 'image_author');
            $table->dropColumn('title_lt');
            $table->dropColumn('permalink_lt');
            $table->dropColumn('source');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->nullable(false)->after('user_id')->change();
        });

        DB::table('news')->where('role_id', '!=', 24)->where('role_id', '>', '19')->update(['role_id' => 1]);
        DB::table('news')->where('role_id', '<=', 3)->update(['role_id' => 1]);

        Schema::table('news', function (Blueprint $table) {
            $table->unsignedInteger('category_id')->nullable()->change();
            $table->unsignedInteger('user_id')->after('id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->unsignedInteger('other_lang_id')->nullable()->index()->after('lang');
            $table->unique(['permalink', 'role_id']);
            $table->unique(['other_lang_id']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('alias')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('posts_tags', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('page_id')->nullable();
            $table->foreign('page_id')->references('id')->on('pages');
            $table->unsignedInteger('tag_id');
            $table->foreign('tag_id')->references('id')->on('tags');
            $table->unsignedInteger('news_id')->nullable();
            $table->foreign('news_id')->references('id')->on('news');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->after('created_at')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
