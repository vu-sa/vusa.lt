<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RefactorSaziningai extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Sažiningai egzaminai

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->renameColumn('contact', 'phone');
            $table->string('email')->nullable()->after('name');
            $table->renameColumn('exam', 'exam_type');
        });

        Schema::table('padaliniai', function (Blueprint $table) {
            $table->increments('id')->change();
        });

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->unsignedInteger('padalinys_id')->nullable()->after('padalinys');
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
            $table->string('padalinys', 10)->comment('Should be deprecated')->change();
        });

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->unsignedInteger('students_need')->change();
            $table->renameColumn('count', 'exam_holders');
            $table->dropColumn('students_registered');
        });

        // Sažiningai stebėtojai

        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->renameColumn('id_p', 'id');
            // TODO: need to set foreign. Right now there exists observers that have no exam 
            // $table->foreign('exam_uuid')->references('uuid')->on('saziningai_exams')->change(); 

            $table->renameColumn('name_p', 'name');
            $table->string('padalinys_p', 30)->comment('Should be deprecated')->change();

            $table->unsignedInteger('padalinys_id')->default(16);
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');

            $table->renameColumn('contact_p', 'phone');
            $table->renameColumn('status_p', 'has_arrived');
        });

        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('name');
            $table->boolean('present')->default(false);
        });

        $exam_uuids = DB::table('saziningai_exams')->pluck('uuid')->all();

        foreach ($exam_uuids as $exam_uuid) {
            DB::table('saziningai_observers')->where('exam_uuid', $exam_uuid)->update(['present' => true]);
        }

        DB::table('saziningai_observers')->where('present', false)->delete();

        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->foreign('exam_uuid')->references('uuid')->on('saziningai_exams');
            $table->dropColumn('present');
            $table->dropForeign('saziningai_observers_padalinys_id_foreign');
        });

        DB::table('saziningai_observers')->pluck('padalinys_p')->each(function ($padalinys_p) {
            $padalinys_id = DB::table('padaliniai')->where('alias', '=', 'vusa' . $padalinys_p)->value('id');
            DB::table('saziningai_observers')->where('padalinys_p', $padalinys_p)->update(['padalinys_id' => $padalinys_id]);
        });

        DB::table('saziningai_exams')->pluck('padalinys')->each(function ($padalinys_p) {
            $padalinys_id = DB::table('padaliniai')->where('alias', '=', 'vusa' . $padalinys_p)->value('id');
            DB::table('saziningai_exams')->where('padalinys', $padalinys_p)->update(['padalinys_id' => $padalinys_id]);
        });

        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->dropColumn('padalinys_p');
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
        });

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->dropColumn('padalinys');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    /* public function down()
    {
        
    } */
}
