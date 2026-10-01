<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\ContentEditorPairingRequest;
use App\Http\Requests\StoreNewsRequest;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdateNewsRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\News;
use App\Models\Page;
use App\Services\ContentEditorService;
use Illuminate\Http\JsonResponse;

class ContentEditorApiController extends ApiController
{
    public function __construct(private readonly ContentEditorService $editor) {}

    public function show(string $kind, int $record): JsonResponse
    {
        abort_unless(in_array($kind, ['pages', 'news'], true), 404);
        $model = $kind === 'pages' ? Page::class : News::class;
        $item = $model::findOrFail($record);
        $this->authorizeApi('update', $item);

        return $this->jsonSuccess($this->editor->snapshot($item));
    }

    public function storePage(StorePageRequest $request): JsonResponse
    {
        return $this->saved($request, 'pages');
    }

    public function updatePage(UpdatePageRequest $request, Page $page): JsonResponse
    {
        return $this->saved($request, 'pages', $page);
    }

    public function storeNews(StoreNewsRequest $request): JsonResponse
    {
        return $this->saved($request, 'news');
    }

    public function updateNews(UpdateNewsRequest $request, News $news): JsonResponse
    {
        return $this->saved($request, 'news', $news);
    }

    public function pairing(ContentEditorPairingRequest $request, string $kind): JsonResponse
    {
        $model = $kind === 'pages' ? Page::class : News::class;
        $record = $request->validated('record_id') ? $model::findOrFail($request->validated('record_id')) : null;

        return $this->jsonSuccess($this->editor->pairing($kind, $record, $request->validated('target_id'), $request->validated('lang'), $request->user()));
    }

    private function saved(StorePageRequest|UpdatePageRequest|StoreNewsRequest|UpdateNewsRequest $request, string $kind, Page|News|null $record = null): JsonResponse
    {
        $item = $this->editor->save($kind, $request->validated(), $request->user(), $record);
        $snapshot = $this->editor->snapshot($item);
        foreach ($snapshot['content']['parts'] as $index => &$part) {
            $part['key'] = $request->validated('content.parts.'.$index.'.key');
        }
        unset($part);

        return $this->jsonSuccess($snapshot);
    }
}
