<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\TextBoxSubmissionsExport;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\ContentPartScopedRequest;
use App\Http\Requests\Api\Admin\IndexTextBoxSubmissionRequest;
use App\Models\ContentPart;
use App\Models\Page;
use App\Models\TextBoxSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TextBoxSubmissionApiController extends ApiController
{
    public function index(IndexTextBoxSubmissionRequest $request): JsonResponse
    {
        $this->requireAuth($request);

        $this->authorizeApi('view', $this->pageForContentPart($request->integer('content_part_id')));

        $paginator = TextBoxSubmission::query()
            ->where('content_part_id', $request->content_part_id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate($request->getPerPage());

        $paginator->through(fn (TextBoxSubmission $submission) => [
            'id' => $submission->id,
            'text' => $submission->text,
            'submitted_by' => $submission->user?->name ?? 'Anonymous', /** @phpstan-ignore nullsafe.neverNull */
            'created_at' => $submission->created_at->toIso8601String(),
        ]);

        return $this->jsonPaginated($paginator);
    }

    public function destroy(Request $request, TextBoxSubmission $submission): JsonResponse
    {
        $this->requireAuth($request);

        $this->authorizeApi('update', $this->pageForContentPart($submission->content_part_id));

        $submission->delete();

        return $this->jsonSuccess(null, __('forms.messages.submission_deleted'));
    }

    public function destroyAll(ContentPartScopedRequest $request): JsonResponse
    {
        $this->requireAuth($request);

        $this->authorizeApi('update', $this->pageForContentPart($request->integer('content_part_id')));

        TextBoxSubmission::query()
            ->where('content_part_id', $request->integer('content_part_id'))
            ->delete();

        return $this->jsonSuccess(null, __('forms.messages.all_submissions_deleted'));
    }

    public function export(ContentPartScopedRequest $request): StreamedResponse
    {
        $this->requireAuth($request);

        $contentPart = ContentPart::findOrFail($request->content_part_id);

        $this->authorizeApi('view', $this->pageForContentPart($contentPart));

        $pageTitle = Page::query()
            ->where('content_id', $contentPart->content_id)
            ->value('title');

        $slug = $pageTitle
            ? preg_replace('/[^a-z0-9]+/', '-', strtolower($pageTitle))
            : 'page';

        $fileName = "{$slug}-atsakymai.xlsx";

        return new TextBoxSubmissionsExport($contentPart)->download($fileName);
    }

    /**
     * Resolve the Page that owns a content part so submissions can be authorized
     * against the parent page's permissions (submissions have no policy of their own).
     */
    protected function pageForContentPart(ContentPart|int $contentPart): Page
    {
        $contentId = $contentPart instanceof ContentPart
            ? $contentPart->content_id
            : ContentPart::query()->whereKey($contentPart)->value('content_id');

        return Page::query()->where('content_id', $contentId)->firstOrFail();
    }
}
