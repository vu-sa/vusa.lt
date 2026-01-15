<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TutorialApiController extends ApiController
{
    /**
     * Get tutorial progress for the current user.
     */
    public function progress(Request $request): JsonResponse
    {
        $user = $this->requireAuth($request);

        $completedTutorials = $user->completedTutorials ?? [];

        return $this->jsonSuccess([
            'completedTutorials' => $completedTutorials,
        ]);
    }
}
