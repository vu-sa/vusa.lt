<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Menu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_new', function (Blueprint $table) {
            $table->id();
            $table->mediumInteger('pid')->default(0);
            $table->string('text', 100);
            $table->string('lang', 2)->default('lt');
            $table->string('url', 255)->nullable()->default(NULL);
            $table->smallInteger('order');
            $table->tinyInteger('show')->default(1);
            $table->tinyInteger('readonly')->default(0);
            $table->mediumInteger('creator');
            $table->timestamp('creator_time')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_new');
    }
}
