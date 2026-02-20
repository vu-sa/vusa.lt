<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends ApiController
{
    /**
     * Search documents (public endpoint).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Document::query()
            ->select(['id', 'title', 'anonymous_url'])
            ->where('is_active', true)
            ->whereNotNull('anonymous_url');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('title', 'LIKE', "%{$search}%");
        }

        // Limit results to prevent memory issues
        $limit = min($request->get('limit', 20), 50); // Max 50 results
        $documents = $query->limit($limit)->get();

        return $this->jsonSuccess($documents);
    }
}
