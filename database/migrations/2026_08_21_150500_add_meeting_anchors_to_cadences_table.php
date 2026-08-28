<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Several bodies open and close a term at a named sitting — the ataskaitinė-rinkiminė
     * konferencija — rather than on a calendar date somebody types in. Anchoring the boundary
     * to that meeting is what keeps the two from drifting when the sitting is moved.
     */
    public function up(): void
    {
        Schema::table('cadences', function (Blueprint $table) {
            $table->char('start_meeting_id', 26)->nullable()->after('institution_id');
            $table->char('end_meeting_id', 26)->nullable()->after('start_meeting_id');

            $table->foreign('start_meeting_id')->references('id')->on('meetings')->nullOnDelete();
            $table->foreign('end_meeting_id')->references('id')->on('meetings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cadences', function (Blueprint $table) {
            $table->dropForeign(['start_meeting_id']);
            $table->dropForeign(['end_meeting_id']);
            $table->dropColumn(['start_meeting_id', 'end_meeting_id']);
        });
    }
};
