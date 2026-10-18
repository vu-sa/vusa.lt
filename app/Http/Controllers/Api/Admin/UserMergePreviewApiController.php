<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\MergeUsersRequest;
use App\Services\UserMergeDutyEmails;
use Illuminate\Http\JsonResponse;

class UserMergePreviewApiController extends ApiController
{
    public function __invoke(MergeUsersRequest $request, UserMergeDutyEmails $emails): JsonResponse
    {
        return $this->jsonSuccess($emails->preview(
            $request->keptUser(), $request->sourceUsers(), $request->validated('login_email'), $request->user(),
        ));
    }
}
