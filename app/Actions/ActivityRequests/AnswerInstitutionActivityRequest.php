<?php

namespace App\Actions\ActivityRequests;

use App\Actions\GetInstitutionManagers;
use App\Actions\RecordMeeting;
use App\Enums\InstitutionActivityAnswer;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\MeetingType;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\User;
use App\Notifications\InstitutionActivityNotMineNotification;
use App\Services\CheckInService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AnswerInstitutionActivityRequest
{
    public function __construct(private readonly CheckInService $checkIns, private readonly RecordMeeting $recordMeeting) {}

    /** @param list<array{date: string, type: string, time?: string|null}> $meetings */
    public function execute(InstitutionActivityRequest $request, InstitutionActivityAnswer $answer, ?Carbon $meetingAt = null, ?MeetingType $meetingType = null, array $meetings = []): void
    {
        DB::transaction(function () use ($request, $answer, $meetingAt, $meetingType, $meetings): void {
            $institution = Institution::query()->lockForUpdate()->find($request->institution_id);
            $request = InstitutionActivityRequest::query()->lockForUpdate()->find($request->id);
            $accepts = $answer === InstitutionActivityAnswer::Met ? $request?->acceptsMeetings() : $request?->isOpen();
            if ($institution === null || $request === null || ! $accepts || $request->recipient === null) {
                return;
            }
            $request->setRelation('institution', $institution);
            $start = $request->period_start->copy();
            $end = $request->periodEnd();
            if (($answer === InstitutionActivityAnswer::Complete && $request->campaign_type !== InstitutionActivityCampaign::MissingMeetings)
                || ($answer === InstitutionActivityAnswer::NotMet && $request->campaign_type !== InstitutionActivityCampaign::ActivityConfirmation)) {
                throw ValidationException::withMessages(['answer' => __('activity_requests.invalid_answer')]);
            }
            $known = $institution->meetings()->whereDate('start_time', '>=', $start)->whereDate('start_time', '<=', $end)->orderByDesc('start_time')->get();
            if ($answer === InstitutionActivityAnswer::NotMet && $known->isNotEmpty()) {
                throw ValidationException::withMessages(['answer' => __('activity_requests.conflicting_meeting')]);
            }
            if ($answer === InstitutionActivityAnswer::Complete && $request->incompleteMeetings()->isNotEmpty()) {
                throw ValidationException::withMessages(['answer' => __('activity_requests.incomplete_remaining')]);
            }
            if ($answer === InstitutionActivityAnswer::Met) {
                $recorded = [];
                if ($meetings === [] && $meetingAt !== null) {
                    $meetings = [['date' => $meetingAt->toDateString(), 'type' => ($meetingType ?? MeetingType::InPerson)->value, 'time' => $meetingAt->format('H:i')]];
                }
                if ($meetings === []) {
                    throw ValidationException::withMessages(['meetings' => __('activity_requests.meetings_required')]);
                }
                foreach ($meetings as $row) {
                    $type = MeetingType::from($row['type']);
                    $at = Carbon::parse($row['date'].' '.($type->isDateOnly() ? '23:59' : $row['time']));
                    if ($at->toDateString() < $start->toDateString() || $at->toDateString() > $end->toDateString()) {
                        throw ValidationException::withMessages(['meetings' => __('activity_requests.outside_period')]);
                    }
                    $meeting = $institution->meetings()->where('start_time', $at)->where('type', $type)->first();
                    if ($meeting === null) {
                        $meeting = $this->recordMeeting->execute($institution, $at, $type);
                        $recorded[] = $meeting->id;
                    }
                    $request->meetings()->syncWithoutDetaching([$meeting->id]);
                    $request->meeting_id ??= $meeting->id;
                }
                // The meeting listener closes colleagues' questions; name who answered so they see it.
                InstitutionActivityRequest::query()->whereKeyNot($request->id)->where('resolution_source', 'meeting')
                    ->whereIn('meeting_id', $recorded)->whereNull('resolved_by_request_id')
                    ->update(['resolved_by_request_id' => $request->id]);
            }
            if ($answer === InstitutionActivityAnswer::NotMet || $answer === InstitutionActivityAnswer::Complete) {
                if ($answer === InstitutionActivityAnswer::Complete && $known->isNotEmpty()) {
                    $start = $known->first()->start_time->copy()->startOfDay()->addDay()->max($start);
                }
                $this->recordUncoveredGap($request, $start);
                InstitutionActivityRequest::query()->open()->where('institution_id', $institution->id)
                    ->where('campaign_type', $request->campaign_type)->whereKeyNot($request->id)
                    ->whereDate('period_start', '>=', $request->period_start)
                    ->where(fn ($query) => $query->whereDate('period_end', '<=', $end)->orWhere(fn ($legacy) => $legacy->whereNull('period_end')->whereDate('created_at', '<=', $end)))
                    ->update(['resolved_at' => now(), 'resolution_source' => 'confirmed_period', 'resolved_by_request_id' => $request->id]);
            }
            $request->answer = $answer;
            $request->answered_at = now();
            $request->resolved_at = null;
            $request->resolution_source = null;
            $request->resolved_by_request_id = null;
            $request->save();
            if ($answer === InstitutionActivityAnswer::NotMine) {
                $coordinators = $request->requestedBy !== null ? collect([$request->requestedBy]) : GetInstitutionManagers::execute($institution);
                $coordinators = $coordinators->reject(fn (User $user): bool => $user->is($request->recipient));
                Notification::send($coordinators, new InstitutionActivityNotMineNotification($request)->afterCommit());
            }
        });
    }

    private function recordUncoveredGap(InstitutionActivityRequest $request, Carbon $start): void
    {
        foreach ($request->uncoveredPeriods($start) as [$from, $to]) {
            $this->recordGap($request, $from, $to);
        }
    }

    private function recordGap(InstitutionActivityRequest $request, Carbon $start, Carbon $end): void
    {
        $checkIn = $this->checkIns->create($request->recipient, $request->institution, $start, $end, __('activity_requests.check_in_note'));
        $request->checkIns()->attach($checkIn->id);
        $request->check_in_id ??= $checkIn->id;
    }
}
