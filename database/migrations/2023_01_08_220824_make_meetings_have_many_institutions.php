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
        Schema::rename('institution_meeting_matter', 'agenda_items');

        Schema::table('agenda_items', function (Blueprint $table) {
            $table->string('title', 255);
            $table->time('start_time')->nullable();
            $table->string('outcome', 255)->nullable();
        });

        Schema::rename('institution_meetings', 'meetings');

        Schema::rename('goal_institution_matter', 'goal_matter');

        Schema::create('institution_meeting', function (Blueprint $table) {
            $table->foreignUlid('institution_id')->constrained();
            $table->foreignUlid('meeting_id')->constrained();
            $table->primary(['institution_id', 'meeting_id']);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // rename indexes
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->renameIndex('institution_meeting_matter_matter_id_foreign', 'agenda_items_matter_id_foreign');
            $table->renameIndex('institution_meeting_matter_meeting_id_foreign', 'agenda_items_meeting_id_foreign');
        });

        Schema::table('goal_matter', function (Blueprint $table) {
            $table->renameIndex('goal_institution_matter_matter_id_foreign', 'goal_matter_matter_id_foreign');
            $table->foreign('matter_id')->references('id')->on('matters')->onDelete('cascade');
        });

        Schema::table('goal_matter', function (Blueprint $table) {
            $table->foreign('goal_id')->references('id')->on('goals')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('agenda_items', 'institution_meeting_matter');

        Schema::table('institution_meeting_matter', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->dropColumn('start_time');
            $table->dropColumn('outcome');
        });

        Schema::rename('meetings', 'institution_meetings');

        Schema::rename('goal_matter', 'goal_institution_matter');

        Schema::dropIfExists('institution_meeting');

        // rename indexes
        Schema::table('institution_meeting_matter', function (Blueprint $table) {
            $table->renameIndex('agenda_items_matter_id_foreign', 'institution_meeting_matter_matter_id_foreign');
            $table->renameIndex('agenda_items_meeting_id_foreign', 'institution_meeting_matter_meeting_id_foreign');
        });

        Schema::table('goal_institution_matter', function (Blueprint $table) {
            $table->renameIndex('goal_matter_matter_id_foreign', 'goal_institution_matter_matter_id_foreign');
            $table->dropForeign(['matter_id']);
        });

        Schema::table('goal_institution_matter', function (Blueprint $table) {
            $table->dropForeign(['goal_id']);
        });
    }
};
