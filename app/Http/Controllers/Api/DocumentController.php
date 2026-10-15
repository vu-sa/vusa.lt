<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\DocumentRecommendationSearchRequest;
use App\Models\Document;
use App\Services\Typesense\DocumentRecommendations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends ApiController
{
    public function recommendations(DocumentRecommendationSearchRequest $request, DocumentRecommendations $recommendations): JsonResponse
    {
        return $this->jsonSuccess(['ids' => $recommendations->matchingIds($request->string('q')->toString())]);
    }

    /**
     * Search documents (public endpoint).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Document::query()
            ->published()
            ->select(['id', 'title', 'anonymous_url']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('title', 'LIKE', "%{$search}%");
        }

        // Limit results to prevent memory issues
        $limit = min($request->input('limit', 20), 50); // Max 50 results
        $documents = $query->limit($limit)->get();

        return $this->jsonSuccess($documents);
    }
}
