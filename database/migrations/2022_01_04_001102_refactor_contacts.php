<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->unsignedInteger('pid')->nullable();
            $table->string('name');
            $table->string('alias')->index();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('duties_institutions_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('alias')->index();
            $table->text('description')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('duties_institutions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pid')->nullable();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('alias')->index();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('type_id')->nullable();
            $table->foreign('type_id')->references('id')->on('duties_institutions_types');
            $table->unsignedInteger('role_id')->nullable()->comment('Should be changed to padaliniai_id.');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['pid', 'alias']);
            $table->json('attributes')->nullable();
        });

        Schema::create('duties', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('type_id')->comment('Studentų atstovas, koordinatorius, etc.');
            $table->foreign('type_id')->references('id')->on('duties_types');
            $table->unsignedInteger('institution_id');
            $table->foreign('institution_id')->references('id')->on('duties_institutions');
            $table->string('email')->nullable()
                ->comment('Commonly the @vusa.lt email address, which is used as the OAuth login. Personal mail is stored in users.email.');
            $table->json('attributes')->nullable()->comment('For specifying, e.g. study programme.');
            $table->unsignedInteger('places_to_occupy')->default(1)->nullable()->comment('Full number of positions to occupy for this duty');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('duties_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('duty_id');
            $table->foreign('duty_id')->references('id')->on('duties');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
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
