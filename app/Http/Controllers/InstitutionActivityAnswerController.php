<?php

namespace App\Http\Controllers;

use App\Actions\ActivityRequests\AnswerInstitutionActivityRequest;
use App\Enums\InstitutionActivityAnswer;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\MeetingType;
use App\Http\Requests\StoreInstitutionActivityAnswerRequest;
use App\Models\InstitutionActivityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * The page behind "Ar vyko posėdis?" email links. Mail scanners open every link, so a GET only
 * asks; the answer is recorded by the POST the recipient confirms.
 */
class InstitutionActivityAnswerController extends Controller
{
    public function show(Request $request, InstitutionActivityRequest $activityRequest): View
    {
        $activityRequest->load(['institution', 'recipient', 'requestedBy', 'meeting', 'checkIn', 'meetings', 'resolvedByRequest.recipient']);
        if ($activityRequest->institution === null || $activityRequest->recipient === null || $activityRequest->expires_at->isPast()) {
            return view('activity-answers.unavailable');
        }
        app()->setLocale(in_array($activityRequest->locale, ['lt', 'en'], true) ? $activityRequest->locale : app()->getLocale());

        $others = InstitutionActivityRequest::query()
            ->open()
            ->with('institution')
            ->whereHas('institution')->whereHas('recipient')
            ->where('send_id', $activityRequest->send_id)
            ->where('recipient_id', $activityRequest->recipient_id)
            ->whereKeyNot($activityRequest->id)
            ->get();

        return view('activity-answers.show', [
            'activityRequest' => $activityRequest,
            'chosen' => InstitutionActivityAnswer::tryFrom((string) $request->query('answer')),
            'others' => $others,
            'meetingTypes' => MeetingType::cases(),
            'knownMeetings' => $activityRequest->institution->meetings()->whereDate('start_time', '>=', $activityRequest->period_start)->whereDate('start_time', '<=', $activityRequest->periodEnd())->orderBy('start_time')->get(),
            'knownCheckIns' => $activityRequest->institution->checkIns()->whereDate('end_date', '>=', $activityRequest->period_start)->whereDate('start_date', '<=', $activityRequest->periodEnd())->orderBy('start_date')->get(),
            'uncovered' => $activityRequest->campaign_type === InstitutionActivityCampaign::ActivityConfirmation && $activityRequest->isOpen() ? $activityRequest->uncoveredPeriods() : [],
            'incompleteMeetings' => $activityRequest->campaign_type === InstitutionActivityCampaign::MissingMeetings ? $activityRequest->incompleteMeetings() : collect(),
            ...$this->untilChoices($activityRequest),
        ]);
    }

    public function store(
        StoreInstitutionActivityAnswerRequest $request,
        InstitutionActivityRequest $activityRequest,
        AnswerInstitutionActivityRequest $answer,
    ): RedirectResponse {
        $chosen = InstitutionActivityAnswer::from($request->validated('answer'));
        if (! ($chosen === InstitutionActivityAnswer::Met ? $activityRequest->acceptsMeetings() : $activityRequest->isOpen())) {
            return redirect()->to($activityRequest->answerUrl());
        }

        $until = $request->validated('until');
        $answer->execute($activityRequest, $chosen, meetings: $request->validated('meetings', []), until: is_string($until) ? Carbon::parse($until) : null);

        return redirect()->to($activityRequest->answerUrl());
    }

    /**
     * The app's check-in presets for "Ne, nevyko", so one answer can also cover the weeks ahead.
     * Capped at three months and at the day before a planned meeting, as the check-in form is.
     *
     * @return array{untilOptions: list<array{value: string, label: string}>, untilMax: string}
     */
    private function untilChoices(InstitutionActivityRequest $activityRequest): array
    {
        $periodEnd = $activityRequest->periodEnd()->copy()->startOfDay();
        $nextMeeting = $activityRequest->institution->meetings()->whereDate('start_time', '>', $periodEnd)->min('start_time');
        $max = today()->addMonths(3);
        if ($nextMeeting !== null) {
            $max = $max->min(Carbon::parse($nextMeeting)->startOfDay()->subDay());
        }

        $options = [['value' => $periodEnd->toDateString(), 'label' => __('activity_requests.until.until_period_end', ['date' => $periodEnd->toDateString()])]];
        foreach ([[today()->addDays(14), 'activity_requests.until.two_weeks'], [today()->endOfMonth()->startOfDay(), 'activity_requests.until.month_end']] as [$date, $label]) {
            $date = $date->min($max);
            if ($date->gt($periodEnd) && ! in_array($date->toDateString(), array_column($options, 'value'), true)) {
                $options[] = ['value' => $date->toDateString(), 'label' => __($label, ['date' => $date->toDateString()])];
            }
        }

        return ['untilOptions' => $options, 'untilMax' => $max->max($periodEnd)->toDateString()];
    }
}
