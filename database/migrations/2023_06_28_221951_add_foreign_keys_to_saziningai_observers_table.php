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
        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->foreign(['flow'])->references(['id'])->on('saziningai_exam_flows')->onDelete('CASCADE');
            $table->foreign(['exam_uuid'])->references(['uuid'])->on('saziningai_exams');
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->dropForeign('saziningai_observers_flow_foreign');
            $table->dropForeign('saziningai_observers_exam_uuid_foreign');
            $table->dropForeign('saziningai_observers_padalinys_id_foreign');
        });
    }
};
