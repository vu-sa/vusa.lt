<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\UpdateOnboardingItemRequest;
use App\Http\Requests\Api\Admin\UpdateOnboardingSummaryRequest;
use App\Services\OnboardingChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingApiController extends ApiController
{
    public function show(Request $request, OnboardingChecklist $checklist): JsonResponse
    {
        return $this->jsonSuccess($checklist->present($this->requireAuth($request)));
    }

    public function updateItem(UpdateOnboardingItemRequest $request, string $item, OnboardingChecklist $checklist): JsonResponse
    {
        return $this->jsonSuccess($checklist->updateItem($this->requireAuth($request), $item, $request->validated('status')));
    }

    public function updateSummary(UpdateOnboardingSummaryRequest $request, OnboardingChecklist $checklist): JsonResponse
    {
        return $this->jsonSuccess($checklist->setHidden($this->requireAuth($request), (bool) $request->validated('hidden')));
    }
}
