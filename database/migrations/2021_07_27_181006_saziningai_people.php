<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SaziningaiPeople extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saziningai_people', function (Blueprint $table) {
            $table->id('id_p');
            $table->string('exam_uuid', 30);
            $table->string('name_p', 100);
            $table->string('padalinys_p', 30);
            $table->string('contact_p', 100);
            $table->smallInteger('flow');
            $table->dateTime('dateRegistered')->useCurrent();
            $table->string('status_p', 11);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saziningai_people');
    }
}
