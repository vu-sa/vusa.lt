<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreTextBoxSubmissionRequest;
use App\Models\ContentPart;
use App\Models\TextBoxSubmission;
use Illuminate\Http\JsonResponse;

class TextBoxSubmissionController extends ApiController
{
    public function store(StoreTextBoxSubmissionRequest $request): JsonResponse
    {
        $contentPart = ContentPart::findOrFail($request->content_part_id);

        if ($contentPart->type !== 'text-box') {
            return $this->jsonError('This content part does not accept submissions.', 422);
        }

        TextBoxSubmission::create([
            'content_part_id' => $contentPart->id,
            'text' => $request->text,
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
        ]);

        return $this->jsonCreated(null, 'Submission received');
    }
}
