<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('username', 'email');
            $table->renameColumn('realname', 'name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->change();
            $table->string('name')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('email', 'username');
            $table->dropUnique('users_email_unique');
            $table->renameColumn('name', 'realname');
            $table->dropColumn('email_verified_at');
        });
    }
}
