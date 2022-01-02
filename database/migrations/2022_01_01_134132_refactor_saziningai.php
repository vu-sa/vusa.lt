<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        
        Schema::rename('saziningai', 'saziningai_exams');

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->renameColumn('contact', 'phone');
            $table->string('email')->nullable();
            $table->renameColumn('exam', 'exam_type');
        });

        Schema::table('padaliniai', function (Blueprint $table) {
            $table->increments('id')->change();
        });

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->unsignedInteger('padalinys_id')->nullable();
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
            $table->string('padalinys', 10)->comment('Should be deprecated')->change();
        });

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->renameColumn('count', 'exam_holders');
            $table->renameColumn('registration_time', 'created_at');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->dropColumn('students_registered');
        });

        // Sažiningai stebėtojai

        Schema::rename('saziningai_people', 'saziningai_observers');

        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->renameColumn('id_p', 'id');
            $table->foreign('exam_uuid')->references('uuid')->on('saziningai_exams')->change();

            $table->renameColumn('name_p', 'name');

            $table->string('padalinys_p', 30)->comment('Should be deprecated')->change();

            $table->unsignedInteger('padalinys_id');
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');

            $table->renameColumn('contact_p', 'phone');
            $table->string('email')->nullable();

            $table->renameColumn('dateRegistered', 'created_at');
            
            $table->renameColumn('status_p', 'has_arrived');
        });

        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent()->change();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
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
