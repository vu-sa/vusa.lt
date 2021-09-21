<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Contacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->nullable()->default(NULL);
            $table->string('duties', 100)->nullable()->default(NULL);
            $table->string('phone', 30)->nullable()->default(NULL);
            $table->string('email', 50)->nullable()->default(NULL);
            $table->string('infoText', 10000)->nullable()->default(NULL);
            $table->string('image', 175)->nullable()->default(NULL);
            $table->string('groupname', 50);
            $table->string('grouptitle', 200);
            $table->string('name_short', 60)->nullable()->default(NULL);
            $table->string('name_full', 100)->nullable()->default(NULL);
            $table->string('address', 100)->nullable()->default(NULL);
            $table->string('webpage', 50)->nullable()->default(NULL);
            $table->string('members', 300)->nullable()->default(NULL);
            $table->smallInteger('contactOrder')->nullable()->default(NULL);
            $table->string('contactGroup', 100)->nullable()->default(NULL);
            $table->string('lang', 5)->default('lt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
    }
}
