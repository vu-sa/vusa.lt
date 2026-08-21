<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Nuotolinis" as an explicit flag rather than an empty `location`.
 *
 * `location` is free text an editor fills in for an address; a remote event has no address at
 * all, and inferring "online" from an empty field left the map/geocoding code guessing. This
 * applies to any event, not only ones announcing a meeting — AnnounceMeetingInCalendar seeds it
 * from Meeting::type when the meeting is remote, but it stays editable afterward.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar', function (Blueprint $table): void {
            $table->boolean('is_remote')->default(false)->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('calendar', function (Blueprint $table): void {
            $table->dropColumn('is_remote');
        });
    }
};
