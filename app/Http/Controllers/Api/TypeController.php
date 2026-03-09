<?php

namespace App\Http\Controllers\Api;

use App\Models\Type;
use Illuminate\Http\JsonResponse;

class TypeController extends ApiController
{
    /**
     * Get all types (public endpoint).
     */
    public function index(): JsonResponse
    {
        $types = Type::all();

        return $this->jsonSuccess($types);
    }
}
