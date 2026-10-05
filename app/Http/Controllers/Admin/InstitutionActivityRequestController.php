<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ActivityRequests\SendInstitutionActivityRequests;
use App\Enums\InstitutionActivityCampaign;
use App\Http\Controllers\AdminController;
use App\Http\Requests\ActivityRequests\StoreInstitutionActivityRequestsRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class InstitutionActivityRequestController extends AdminController
{
    public function store(StoreInstitutionActivityRequestsRequest $request, SendInstitutionActivityRequests $send): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $result = $send->send($request->institutions(), $user, $request->validated('note'), InstitutionActivityCampaign::from($request->validated('campaign_type')), $request->pairs());
        $sent = $result['queued_requests'];

        return back()->with('success', __('activity_requests.queued', ['count' => $sent, 'recipients' => $result['queued_recipients'], 'emails' => $result['queued_emails']]));
    }
}
