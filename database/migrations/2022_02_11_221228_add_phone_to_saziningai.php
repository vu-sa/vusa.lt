<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhoneToSaziningai extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('saziningai', function (Blueprint $table) {
            $table->string('phone')->nullable();
        });

        Schema::table('saziningai_people', function (Blueprint $table) {
            $table->string('phone_p')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('saziningai', function (Blueprint $table) {
            //
        });
    }
}
