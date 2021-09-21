<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Sidebar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sidebar', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('title', 100);
            $table->text('value')->nullable()->default(NULL);
            $table->text('url');
            $table->string('lang', 2)->default('lt');
            $table->smallInteger('order');
            $table->tinyInteger('hide');
            $table->smallInteger('editor');
            $table->smallInteger('editorG');
            $table->timestamp('editor_time')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sidebar');
    }
}
