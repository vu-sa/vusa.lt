<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\IndexPublicDocumentsRequest;
use App\Http\Requests\DocumentRecommendationSearchRequest;
use App\Models\Document;
use App\Services\Typesense\DocumentRecommendations;
use Illuminate\Http\JsonResponse;

class DocumentController extends ApiController
{
    public function recommendations(DocumentRecommendationSearchRequest $request, DocumentRecommendations $recommendations): JsonResponse
    {
        return $this->jsonSuccess(['ids' => $recommendations->matchingIds($request->string('q')->toString())]);
    }

    /**
     * Search documents (public endpoint).
     */
    public function index(IndexPublicDocumentsRequest $request): JsonResponse
    {
        $query = Document::query()
            ->published()
            ->select(['id', 'title', 'anonymous_url']);

        $search = $request->validated('search');

        if (filled($search)) {
            $query->where('title', 'LIKE', "%{$search}%");
        }

        $documents = $query->limit($request->limit())->get();

        return $this->jsonSuccess($documents);
    }
}
