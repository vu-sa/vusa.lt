<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let a calendar event stand for a meeting.
 *
 * VU SA bodies have been announcing posėdžiai as standalone calendar events for over a decade
 * while the agenda and the protokolai lived elsewhere. This is the join that makes them one record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar', function (Blueprint $table): void {
            $table->char('meeting_id', 26)->nullable()->after('tenant_id');
            $table->foreign('meeting_id')->references('id')->on('meetings')->nullOnDelete();
            $table->index('meeting_id');
        });
    }

    public function down(): void
    {
        Schema::table('calendar', function (Blueprint $table): void {
            $table->dropForeign(['meeting_id']);
            $table->dropIndex(['meeting_id']);
            $table->dropColumn('meeting_id');
        });
    }
};
