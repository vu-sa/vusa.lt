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
        Schema::table('institution_meeting', function (Blueprint $table) {
            $table->foreign(['meeting_id'])->references(['id'])->on('meetings');
            $table->foreign(['institution_id'])->references(['id'])->on('institutions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('institution_meeting', function (Blueprint $table) {
            $table->dropForeign('institution_meeting_meeting_id_foreign');
            $table->dropForeign('institution_meeting_institution_id_foreign');
        });
    }
};
