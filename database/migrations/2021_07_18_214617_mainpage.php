<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Mainpage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mainPage', function (Blueprint $table) {
            $table->id();
            $table->string('link', 100)->nullable()->default(NULL);
            $table->smallInteger('newsID')->nullable()->default(NULL);
            $table->string('text', 100)->nullable()->default(NULL);
            $table->string('image', 100)->nullable()->default(NULL);
            $table->string('position', 100);
            $table->smallInteger('orderID')->nullable()->default(NULL);
            $table->string('type', 60)->nullable()->default(NULL);
            $table->string('moduleName', 100)->nullable()->default('calendar');
            $table->smallInteger('groupID');
            $table->string('lang', 2)->nullable()->default(NULL);
            $table->timestamp('created_time')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mainPage');
    }
}
