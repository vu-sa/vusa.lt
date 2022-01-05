<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('navigation', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->renameColumn('pid', 'parent_id');
            $table->renameColumn('text', 'name');
            $table->renameColumn('show', 'is_active');
            $table->unsignedInteger('pid')->default(0)->change();
            $table->string('url')->nullable(false)->change();
            $table->dropColumn('readonly');
            $table->dropColumn('creator');
            $table->dropColumn('creator_time');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('navigation', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable()->after('parent_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedInteger('padalinys_id')->after('user_id')->default(16);
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
            // TODO: need to set unique
            // $table->unique(['parent_id', 'lang', 'order', 'padalinys_id']);
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
