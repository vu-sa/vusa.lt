<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Page extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('page', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('title_lt', 200)->nullable()->default(NULL);
            $table->string('permalink', 100)->nullable()->default(NULL);
            $table->string('permalink_lt', 100)->nullable()->default(NULL);
            $table->mediumText('text');
            $table->string('lang', 2)->default('lt');
            $table->string('category', 200);
            $table->tinyInteger('readonly')->default(0);
            $table->tinyInteger('disabled')->default(0);
            $table->smallInteger('editor');
            $table->smallInteger('editorG');
            $table->timestamp('editor_time')->useCurrent()->useCurrentOnUpdate();
            $table->mediumText('mainInfo')->nullable()->default(NULL);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('page');
    }
}
