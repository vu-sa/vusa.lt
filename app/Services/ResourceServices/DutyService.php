<?php

namespace App\Services\ResourceServices;

use App\Models\Institution;
use App\Models\Meeting;
use App\Services\ModelAuthorizer;

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
            ])
            ->get();
    }

}
