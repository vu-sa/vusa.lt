<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorMainPage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('main_page', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->dropColumn('newsID');
            $table->dropColumn('moduleName');
            $table->renameColumn('orderID', 'order');
            $table->boolean('is_active')->default(true)->after('type');
            $table->unsignedInteger('user_id')->default(1)->after('id');
            $table->foreign('user_id')->references('id')->on('users')->after('id');
            $table->unsignedInteger('groupID')->change();
            $table->renameColumn('groupID', 'padalinys_id');
            $table->string('link')->nullable()->change();
        });

        Schema::table('main_page', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent()->change();
            $table->unsignedInteger('padalinys_id')->change()->after('id');
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
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
