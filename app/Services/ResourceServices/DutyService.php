<?php

namespace App\Services\ResourceServices;

use App\Models\Institution;
use App\Models\Meeting;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Facades\DB;

class DutyService
{
    public static function getInstitutionsForUpserts(ModelAuthorizer $authorizer)
    {
        return Institution::select('id', 'name', 'alias', 'tenant_id')
            ->when(! $authorizer->forUser(request()->user())->checkAllRoleables('duties.create.all'),
                function ($query) {
                    $query->whereIn('tenant_id', auth()->user()->tenants->pluck('id'));
                })
            ->whereHas('tenant', function ($query) {
                $query->where('type', '!=', 'pkp');
            })
            ->with('tenant:id,shortname')
            ->get();
    }

    public static function getInstitutionsForDashboard(ModelAuthorizer $authorizer)
    {
        return Institution::select('id', 'name', 'alias', 'tenant_id')
            ->when(! $authorizer->forUser(request()->user())->checkAllRoleables('duties.create.all'),
                function ($query) {
                    $query->whereIn('tenant_id', auth()->user()->tenants->pluck('id'));
                })
            ->whereHas('tenant', function ($query) {
                $query->where('type', '!=', 'pkp');
            })
            ->with([
                'tenant:id,shortname',
                'meetings:id,title,start_time',
                'meetings.agendaItems:id,meeting_id,student_vote,decision,student_benefit',
                'duties.current_users:id,name',
                'duties.types:id,title,slug',
                'checkIns'
            ])
            ->withCount([
                'meetings as upcoming_meetings_count' => function ($query) {
                    $query->where('start_time', '>', now());
                }
            ])
            ->addSelect([
                'last_meeting_date' => Meeting::select('start_time')
                    ->join('institution_meeting', 'meetings.id', '=', 'institution_meeting.meeting_id')
                    ->whereColumn('institution_meeting.institution_id', 'institutions.id')
                    ->orderBy('start_time', 'desc')
                    ->limit(1),
                'days_since_last_meeting' => self::getDaysSinceLastMeetingSql()
            ])
            ->get();
    }

    /**
     * Get database-specific SQL for calculating days since last meeting
     */
    public static function getDaysSinceLastMeetingSql()
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            return DB::raw('CASE 
                WHEN (SELECT start_time FROM meetings 
                      INNER JOIN institution_meeting ON meetings.id = institution_meeting.meeting_id 
                      WHERE institution_meeting.institution_id = institutions.id 
                      ORDER BY start_time DESC LIMIT 1) IS NOT NULL 
                THEN CAST(JULIANDAY("' . now()->format('Y-m-d H:i:s') . '") - JULIANDAY((SELECT start_time FROM meetings 
                                      INNER JOIN institution_meeting ON meetings.id = institution_meeting.meeting_id 
                                      WHERE institution_meeting.institution_id = institutions.id 
                                      ORDER BY start_time DESC LIMIT 1)) AS INTEGER)
                ELSE NULL 
            END');
        }
        
        // MySQL/MariaDB
        return DB::raw('CASE 
            WHEN (SELECT start_time FROM meetings 
                  INNER JOIN institution_meeting ON meetings.id = institution_meeting.meeting_id 
                  WHERE institution_meeting.institution_id = institutions.id 
                  ORDER BY start_time DESC LIMIT 1) IS NOT NULL 
            THEN DATEDIFF(NOW(), (SELECT start_time FROM meetings 
                                  INNER JOIN institution_meeting ON meetings.id = institution_meeting.meeting_id 
                                  WHERE institution_meeting.institution_id = institutions.id 
                                  ORDER BY start_time DESC LIMIT 1))
            ELSE NULL 
        END');
    }
}
