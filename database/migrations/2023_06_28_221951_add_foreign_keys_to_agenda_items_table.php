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
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->foreign(['meeting_id'], 'institution_meeting_matter_meeting_id_foreign')->references(['id'])->on('meetings');
            $table->foreign(['matter_id'], 'institution_meeting_matter_matter_id_foreign')->references(['id'])->on('matters');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->dropForeign('institution_meeting_matter_meeting_id_foreign');
            $table->dropForeign('institution_meeting_matter_matter_id_foreign');
        });
    }
};
