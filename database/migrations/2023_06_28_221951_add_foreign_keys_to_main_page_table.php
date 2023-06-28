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
        Schema::table('main_page', function (Blueprint $table) {
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('main_page', function (Blueprint $table) {
            $table->dropForeign('main_page_padalinys_id_foreign');
        });
    }
};
