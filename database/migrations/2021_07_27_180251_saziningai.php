<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Saziningai extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saziningai', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 30);
            $table->string('name', 30)->nullable()->default('Anonimas');
            $table->string('contact', 100)->nullable()->default(NULL);
            $table->string('exam', 100)->nullable()->default(NULL);
            $table->string('padalinys', 10)->nullable()->default(NULL);
            $table->string('place', 100)->nullable()->default(NULL);
            $table->string('time', 80)->nullable()->default(NULL);
            $table->string('duration', 30)->nullable()->default(NULL);
            $table->string('subject_name', 100)->nullable()->default(NULL);
            $table->smallInteger('count')->nullable()->default(NULL);
            $table->string('students_need')->nullable()->default(NULL);
            $table->smallInteger('students_registered')->nullable()->default(NULL);
            $table->timestamp('registration_time')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saziningai');
    }
}
