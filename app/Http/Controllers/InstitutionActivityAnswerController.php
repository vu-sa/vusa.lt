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
use Illuminate\View\View;

/**
 * The page behind "Ar vyko posėdis?" email links. Mail scanners open every link, so a GET only
 * asks; the answer is recorded by the POST the recipient confirms.
 */
class InstitutionActivityAnswerController extends Controller
{
    public function show(Request $request, InstitutionActivityRequest $activityRequest): View
    {
        $activityRequest->load(['institution', 'recipient', 'requestedBy', 'meeting', 'checkIn', 'meetings']);
        if ($activityRequest->institution === null || $activityRequest->recipient === null || $activityRequest->expires_at->isPast()) {
            return view('activity-answers.unavailable');
        }
        app()->setLocale(in_array($activityRequest->locale, ['lt', 'en'], true) ? $activityRequest->locale : app()->getLocale());

        $others = InstitutionActivityRequest::query()
            ->open()
            ->with('institution')
            ->whereHas('institution')->whereHas('recipient')
            ->where('campaign_type', $activityRequest->campaign_type)
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
            'incompleteMeetings' => $activityRequest->campaign_type === InstitutionActivityCampaign::MissingMeetings ? $activityRequest->incompleteMeetings() : collect(),
        ]);
    }

    public function store(
        StoreInstitutionActivityAnswerRequest $request,
        InstitutionActivityRequest $activityRequest,
        AnswerInstitutionActivityRequest $answer,
    ): RedirectResponse {
        if (! $activityRequest->isOpen()) {
            return redirect()->to($activityRequest->answerUrl());
        }

        $answer->execute($activityRequest, InstitutionActivityAnswer::from($request->validated('answer')), meetings: $request->validated('meetings', []));

        return redirect()->to($activityRequest->answerUrl());
    }
}
