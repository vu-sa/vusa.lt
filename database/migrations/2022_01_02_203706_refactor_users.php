<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('users_groups', 'roles');

        Schema::table('roles', function (Blueprint $table) {
            $table->renameColumn('descr', 'description');
        });
        
        Schema::table('roles', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->string('alias')->after('id')->change();
            $table->string('name')->after('alias');
            $table->text('description')->nullable()->change();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
        
        Schema::table('users', function (Blueprint $table) {
            // $table->dropUnique('users_email_unique');
            $table->string('google_token')->nullable()->after('remember_token');
            $table->text('microsoft_token')->nullable()->after('remember_token');
            $table->string('password')->nullable()->default(NULL)->change();
            $table->renameColumn('disabled', 'is_active');
            $table->renameColumn('gid', 'role_id');
            $table->dropColumn('lastlogin');
            $table->timestamp('last_login')->nullable()->after('remember_token');
            $table->dropUnique('username');
            $table->increments('id')->change();
            $table->timestamp('created_at')->useCurrent()->after('is_active')->change();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->change();
            $table->string('phone')->nullable()->after('email');
        });


        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->change();
            $table->dropColumn('lastlogin_ip');
            // $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
}
