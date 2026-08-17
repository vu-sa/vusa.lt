<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreAdminCommentRequest;
use App\Http\Requests\UpdateAdminCommentRequest;
use App\Models\Comment;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\SharepointFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class CommentController extends AdminController
{
    /**
     * Allowed commentable model types.
     * Only these models can be commented on via user input.
     *
     * @var array<string, class-string>
     */
    private const array ALLOWED_COMMENTABLE_TYPES = [
        'reservation' => Reservation::class,
        'reservation-resource' => ReservationResource::class,
        'institution' => Institution::class,
        'meeting' => Meeting::class,
        'sharepoint-file' => SharepointFile::class,
    ];

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdminCommentRequest $request)
    {
        $validated = $request->validated();

        $typeKey = Str::kebab($validated['commentable_type']);

        if (! isset(self::ALLOWED_COMMENTABLE_TYPES[$typeKey])) {
            return back()->with('error', __('messages.comment.invalid_type'));
        }

        $modelClass = self::ALLOWED_COMMENTABLE_TYPES[$typeKey];
        $model = $modelClass::find($validated['commentable_id']);

        if (! $model) {
            return back()->with('error', __('messages.comment.model_not_found'));
        }

        $this->authorize('view', $model);

        $model->comment($validated['comment']);

        return back()->with('success', $this->entityMessage('created', 'comment'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     */
    public function update(UpdateAdminCommentRequest $request, Comment $comment)
    {
        $this->handleAuthorization('update', $comment);

        // update comment
        $comment->update($request->safe()->only('comment'));

        return back()->with('success', $this->entityMessage('updated', 'comment'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse
     */
    public function destroy(Comment $comment)
    {
        $this->handleAuthorization('delete', $comment);

        // delete comment
        $comment->delete();

        return back()->with('success', $this->entityMessage('deleted', 'comment'));
    }
}
