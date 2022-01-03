<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->renameColumn('editorG', 'padalinys_id');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->after('id')->change();
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedInteger('padalinys_id')->after('user_id')->default(1)->change();
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
            $table->integer('is_active')->default(1)->change();
            $table->text('image_url')->nullable(false)->change();
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
