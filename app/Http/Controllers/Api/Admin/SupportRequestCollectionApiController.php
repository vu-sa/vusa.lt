<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildSupportRequestCollection;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexSupportRequestRequest;
use Illuminate\Http\JsonResponse;

class SupportRequestCollectionApiController extends ApiController
{
    public function index(IndexSupportRequestRequest $request, BuildSupportRequestCollection $builder): JsonResponse
    {
        $requests = $builder->execute($request)['requests'];

        return $this->jsonSuccess([
            'items' => $requests->items(),
            'total' => $requests->total(),
            'per_page' => $requests->perPage(),
            'current_page' => $requests->currentPage(),
            'last_page' => $requests->lastPage(),
        ]);
    }
}
