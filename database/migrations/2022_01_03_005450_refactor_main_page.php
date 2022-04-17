<?php

use App\Models\Users_group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            $table->unsignedInteger('user_id')->nullable()->after('id');
            $table->foreign('user_id')->references('id')->on('users')->after('id');
            $table->unsignedInteger('groupID')->change();
            $table->renameColumn('groupID', 'role_id');
            $table->string('link')->nullable()->change();
        });

        DB::table('main_page')->where('role_id', '=', 23)->delete();
        DB::table('main_page')->where('position', '=', 'additionalInfo')->delete();

        Schema::table('main_page', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent()->change();
            $table->unsignedInteger('role_id')->after('id')->change();
            $table->foreign('role_id')->references('id')->on('roles');
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
