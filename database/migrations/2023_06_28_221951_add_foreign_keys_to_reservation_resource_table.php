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
        Schema::table('reservation_resource', function (Blueprint $table) {
            $table->foreign(['resource_id'])->references(['id'])->on('resources');
            $table->foreign(['reservation_id'])->references(['id'])->on('reservations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reservation_resource', function (Blueprint $table) {
            $table->dropForeign('reservation_resource_resource_id_foreign');
            $table->dropForeign('reservation_resource_reservation_id_foreign');
        });
    }
};
