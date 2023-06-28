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
        Schema::create('padaliniai', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 255)->nullable()->default('padalinys');
            $table->string('fullname', 100);
            $table->string('shortname', 100)->unique();
            $table->string('alias', 20);
            $table->boolean('en')->default(false);
            $table->string('phone', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('shortname_vu', 255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('padaliniai');
    }
};
