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
        Schema::create('saziningai_exams', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid', 30)->unique('uuid uniq');
            $table->string('name', 30)->nullable()->default('Anonimas');
            $table->string('email', 100)->nullable();
            $table->string('exam_type', 100)->nullable();
            $table->unsignedInteger('padalinys_id')->nullable()->index('saziningai_exams_padalinys_id_foreign');
            $table->string('place', 100)->nullable();
            $table->string('duration', 200)->nullable();
            $table->string('subject_name', 100)->nullable();
            $table->integer('exam_holders')->nullable();
            $table->unsignedInteger('students_need')->nullable();
            $table->string('phone', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saziningai_exams');
    }
};
