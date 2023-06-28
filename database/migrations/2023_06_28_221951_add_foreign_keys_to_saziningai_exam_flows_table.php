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
        Schema::table('saziningai_exam_flows', function (Blueprint $table) {
            $table->foreign(['exam_uuid'])->references(['uuid'])->on('saziningai_exams')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('saziningai_exam_flows', function (Blueprint $table) {
            $table->dropForeign('saziningai_exam_flows_exam_uuid_foreign');
        });
    }
};
