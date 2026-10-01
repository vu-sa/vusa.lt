<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\ContentEditorDraftRequest;
use App\Models\ContentEditorDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ContentEditorDraftApiController extends ApiController
{
    public function show(ContentEditorDraftRequest $request, string $kind, string $identity): JsonResponse
    {
        $draft = ContentEditorDraft::where('user_id', $request->user()->id)->where('kind', $kind)->where('identity', $identity)->first();

        return $this->jsonSuccess($draft);
    }

    public function update(ContentEditorDraftRequest $request, string $kind, string $identity): JsonResponse
    {
        return DB::transaction(function () use ($request, $kind, $identity): JsonResponse {
            // Serializes the first insert too, when no draft row exists yet.
            $request->user()->newQuery()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $draft = ContentEditorDraft::where('user_id', $request->user()->id)->where('kind', $kind)->where('identity', $identity)->lockForUpdate()->first();
            if (($draft === null ? 0 : $draft->revision) !== (int) $request->validated('revision')) {
                return response()->json(['success' => false, 'code' => 'draft_conflict', 'data' => $draft], 409);
            }
            if ($request->isMethod('DELETE')) {
                $draft?->delete();

                return $this->jsonSuccess(null);
            }
            $draft ??= new ContentEditorDraft(['user_id' => $request->user()->id, 'kind' => $kind, 'identity' => $identity]);
            $draft->snapshot = $request->validated('snapshot');
            $draft->revision = ($draft->revision ?? 0) + 1;
            $draft->save();

            return $this->jsonSuccess($draft);
        });
    }
}
