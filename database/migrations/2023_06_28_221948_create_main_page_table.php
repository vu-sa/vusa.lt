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
        Schema::create('main_page', function (Blueprint $table) {
            $table->increments('id');
            $table->string('link', 255)->nullable();
            $table->string('text', 100)->nullable();
            $table->string('image', 100)->nullable();
            $table->string('position', 100);
            $table->integer('order')->nullable();
            $table->string('type', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('padalinys_id')->index('main_page_padalinys_id_foreign');
            $table->string('lang', 2)->nullable()->default('lt');
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
        Schema::dropIfExists('main_page');
    }
};
