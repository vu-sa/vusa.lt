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
        Schema::table('goals', function (Blueprint $table) {
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            $table->foreign(['group_id'])->references(['id'])->on('goal_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->dropForeign('goals_padalinys_id_foreign');
            $table->dropForeign('goals_group_id_foreign');
        });
    }
};
