<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaziningaiExamFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saziningai_exam_flows', function (Blueprint $table) {
            $table->id();
            $table->string('exam_uuid', 30);
            $table->foreign('exam_uuid')->references('uuid')->on('saziningai_exams');
            $table->time('start_time');
            $table->integer('duration');
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
        Schema::dropIfExists('saziningai_exam_flows');
    }
}
