<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Calendar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calendar', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('time', 10)->nullable()->default(NULL);
            $table->string('title', 999);
            $table->mediumText('descr')->nullable()->default(NULL);
            $table->string('classname', 30)->nullable()->default(NULL);
            $table->mediumText('url')->nullable()->default(NULL);
            $table->tinyInteger('editor');
            // $table->timestamp('editor_time')->useCurrentOnUpdate();
            $table->string('badge', 10)->nullable()->default('true');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calendar');
    }
}
