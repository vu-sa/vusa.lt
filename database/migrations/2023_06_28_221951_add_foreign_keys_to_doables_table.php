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
        Schema::table('doables', function (Blueprint $table) {
            $table->foreign(['doing_id'])->references(['id'])->on('doings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('doables', function (Blueprint $table) {
            $table->dropForeign('doables_doing_id_foreign');
        });
    }
};
