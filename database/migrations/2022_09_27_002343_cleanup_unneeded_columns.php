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
        // delete table old_roles
        Schema::dropIfExists('old_roles');

        // remove foreign key from navigation table
        Schema::table('navigation', function (Blueprint $table) {
            $table->dropForeign('navigation_user_id_foreign');
        });

        // remove user_id column from navigation
        Schema::table('navigation', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        // remove aside column from page
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('aside');
        });

        // remove personal_access_tokens table
        Schema::dropIfExists('personal_access_tokens');


        // remove table page_views
        Schema::dropIfExists('page_views');

        // remove role_id from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_id');
            $table->dropColumn('team_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // create table old_roles
        Schema::create('old_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // add foreign key to navigation table
        Schema::table('navigation', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
        });

        // add user_id column to navigation
        Schema::table('navigation', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
        });

        // add aside column to page
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('aside')->default(false);
        });

        // add personal_access_tokens table
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tokenable_id');
            $table->string('tokenable_type');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_id', 'tokenable_type']);
        });

        // add table page_views
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('ip');
            $table->string('user_agent');
            $table->timestamps();
        });

        // add role_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('old_roles')->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');
        });
    }
};
