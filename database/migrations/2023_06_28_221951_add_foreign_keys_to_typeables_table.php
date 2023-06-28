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
        Schema::table('typeables', function (Blueprint $table) {
            $table->foreign(['type_id'])->references(['id'])->on('types')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('typeables', function (Blueprint $table) {
            $table->dropForeign('typeables_type_id_foreign');
        });
    }
};
