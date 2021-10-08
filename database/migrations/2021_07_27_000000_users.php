<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Users extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    // TODO: gid cannot be null here
    
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('realname');
            $table->string('email')->nullable()->default(NULL);
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->tinyInteger('disabled')->default(0);
            $table->smallInteger('gid')->nullable()->default(NULL);
            $table->timestamp('lastlogin')->nullable()->default(NULL);
            $table->string('lastlogin_ip', 39)->nullable()->default(NULL);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}