<?php

use App\Models\Meeting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('meeting_kind', 20)->nullable()->after('type');
        });

        if (! Schema::hasColumn('institutions', 'meeting_kind')) {
            return;
        }

        $meetingIds = DB::table('institution_meeting')
            ->join('institutions', 'institutions.id', '=', 'institution_meeting.institution_id')
            ->whereNotNull('institutions.meeting_kind')
            ->distinct()
            ->pluck('institution_meeting.meeting_id');

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('meeting_kind');
        });

        Meeting::withTrashed()->whereKey($meetingIds)->with('institutions.types')
            ->chunkById(100, fn ($meetings) => $meetings->each(fn (Meeting $meeting) => $meeting->refreshTitle()));
    }

    public function down(): void
    {
        if (! Schema::hasColumn('institutions', 'meeting_kind')) {
            Schema::table('institutions', function (Blueprint $table) {
                $table->string('meeting_kind', 20)->nullable()->after('meeting_periodicity_days');
            });
        }

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('meeting_kind');
        });
    }
};
