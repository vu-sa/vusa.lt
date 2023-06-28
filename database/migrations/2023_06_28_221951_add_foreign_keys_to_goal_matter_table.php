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
        Schema::table('goal_matter', function (Blueprint $table) {
            $table->foreign(['matter_id'], 'goal_institution_matter_matter_id_foreign')->references(['id'])->on('matters');
            $table->foreign(['matter_id'])->references(['id'])->on('matters')->onDelete('CASCADE');
            $table->foreign(['goal_id'], 'goal_institution_matter_goal_id_foreign')->references(['id'])->on('goals');
            $table->foreign(['goal_id'])->references(['id'])->on('goals')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goal_matter', function (Blueprint $table) {
            $table->dropForeign('goal_institution_matter_matter_id_foreign');
            $table->dropForeign('goal_matter_matter_id_foreign');
            $table->dropForeign('goal_institution_matter_goal_id_foreign');
            $table->dropForeign('goal_matter_goal_id_foreign');
        });
    }
};
