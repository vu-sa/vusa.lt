<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RefactorBanners extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->dropColumn('type');
            $table->renameColumn('value', 'image_url');
            $table->renameColumn('url', 'link_url');
            $table->renameColumn('hide', 'is_active');
            $table->renameColumn('editor', 'user_id');
            $table->renameColumn('editorG', 'role_id');
        });

        DB::table('banners')->update(['user_id' => 56]);

        Schema::table('banners', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->after('id')->change();
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedInteger('role_id')->after('user_id')->default(1)->change();
            $table->foreign('role_id')->references('id')->on('roles');
            $table->integer('is_active')->default(1)->change();
            $table->text('image_url')->nullable(false)->change();
            $table->unique(['order', 'role_id']);
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
