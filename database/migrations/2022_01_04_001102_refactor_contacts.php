<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->increments('id')->change();
        });
        
        Schema::create('duties_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('alias')->index();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('duties_institutions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('alias')->index();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('duties', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('type_id');
            $table->foreign('type_id')->references('id')->on('duties_types');
            $table->unsignedInteger('institution_id');
            $table->foreign('institution_id')->references('id')->on('duties_institutions');
            $table->string('department')->nullable();
            $table->string('email')->nullable()->comment('Commonly the @vusa.lt email address, which is used as the OAuth login');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('duties_display', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('duty_id');
            $table->foreign('duty_id')->references('id')->on('duties');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedInteger('contact_id');
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->json('attributes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
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
