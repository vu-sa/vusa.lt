<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\RecordAbilitiesRequest;
use Illuminate\Http\JsonResponse;

class RecordAbilitiesApiController extends ApiController
{
    public function index(RecordAbilitiesRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $modelClass = RecordAbilitiesRequest::MODELS[$request->validated('type')];
        $data = [];

        foreach ($modelClass::query()->whereKey($request->validated('ids'))->get() as $model) {
            if (! $user->can('view', $model)) {
                continue;
            }

            $data[(string) $model->getKey()] = [
                'update' => $user->can('update', $model),
                'delete' => $user->can('delete', $model),
            ];
        }

        return $this->jsonSuccess((object) $data);
    }
}
