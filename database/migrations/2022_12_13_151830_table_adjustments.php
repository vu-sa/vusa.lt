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
        // make question_group_id nullable in questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedInteger('question_group_id')->nullable()->change();

            // $table->dropForeign('questions_question_group_id_foreign');
            $table->foreign('question_group_id')->references('id')->on('question_groups')->nullOnDelete();
        });

        // make types description text
        Schema::table('types', function (Blueprint $table) {
            $table->text('description')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // make question_group_id not nullable in questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedBigInteger('question_group_id')->nullable(false)->change();
        });

        // do not cascade
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['question_group_id']);
            $table->foreign('question_group_id')->references('id')->on('question_groups');
        });

        // make types description string
        Schema::table('types', function (Blueprint $table) {
            $table->string('description')->change();
        });
    }
};
