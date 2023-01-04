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
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::table('calendar', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->renameColumn('attributes', 'extra_attributes');
        });

        Schema::table('navigation', function (Blueprint $table) {
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
        });

        Schema::dropIfExists('password_resets');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
