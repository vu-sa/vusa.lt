<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildMailQueuePage;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexMailQueueRequest;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class MailQueueApiController extends ApiController
{
    public function index(IndexMailQueueRequest $request, BuildMailQueuePage $builder): JsonResponse
    {
        $this->authorizeApi('viewAny', Role::class);

        $page = $builder->execute($request);

        return $this->jsonSuccess([
            'items' => $page->items(),
            'total' => $page->total(),
            'per_page' => $page->perPage(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ]);
    }
}
