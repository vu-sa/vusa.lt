<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Faker\Factory;
use App\Models\Saziningai;

class CreateSaziningaiExamFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create table for storing sazininigai exam flows
        Schema::create('saziningai_exam_flows', function (Blueprint $table) {
            $table->increments('id');
            $table->string('exam_uuid', 30);
            $table->foreign('exam_uuid')->references('uuid')->on('saziningai_exams')->cascadeOnDelete();
            $table->datetime('start_time');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Get all times from saziningai_exams and insert them into saziningai_exam_flows
        $saziningai_exams = DB::table('saziningai_exams')->select('time', 'uuid')->get();
        $saziningai_exam_flows = [];

        foreach ($saziningai_exams as $key => $value) {
            $times = explode(' | ', $value->time);
            foreach ($times as $timeKey => $timeValue) {
                if ($timeValue != '') {
                    array_push($saziningai_exam_flows, [
                        'exam_uuid' => $value->uuid,
                        'start_time' => $timeValue,
                    ]);
                }
            }
        }

        foreach ($saziningai_exam_flows as $key => $value) {
            DB::table('saziningai_exam_flows')->insert(
                $value
            );
        }

        // get flow id for each observer, from saziningai_exam_flows and insert

        $observers = DB::table('saziningai_observers')->get(['id', 'exam_uuid', 'flow']);
        
        foreach ($observers as $key => $value) {
            $flow = DB::table('saziningai_exam_flows')->select('id')->where('exam_uuid', $value->exam_uuid)->get()[$value->flow - 1];
            DB::table('saziningai_observers')->where('exam_uuid', $value->exam_uuid)->where('flow', $value->flow)->update(['flow' => $flow->id]);
        }

        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->unsignedInteger('flow')->change();
            $table->foreign('flow')->references('id')->on('saziningai_exam_flows')->cascadeOnDelete();
        });

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->dropColumn('time');
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
