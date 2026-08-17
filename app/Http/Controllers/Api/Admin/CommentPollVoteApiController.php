<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\CommentBroadcast;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\StoreCommentPollVoteRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Support\Commentables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Cast (or retract) a vote on a poll comment. Anyone who can view the parent may
 * vote. Toggle semantics mirror reactions:
 *
 *  - single-choice: selecting your current option retracts it; another option replaces it.
 *  - multiple-choice: each option toggles independently.
 */
class CommentPollVoteApiController extends ApiController
{
    public function toggle(StoreCommentPollVoteRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('vote', $comment);

        // The poll guards and the option allowlist both live on the request now, so they
        // still run in the same order as when this was inline.
        $validated = $request->validated();

        $user = $request->user();
        $optionId = $validated['option_id'];

        $existing = $comment->pollVotes()
            ->where('user_id', $user->id)
            ->where('option_id', $optionId)
            ->first();

        if ($existing) {
            // Toggling your current choice off.
            $existing->delete();
        } else {
            // Single-choice polls hold one vote per user — clear the previous one.
            if (! $comment->pollAllowsMultiple()) {
                $comment->pollVotes()->where('user_id', $user->id)->delete();
            }

            $comment->pollVotes()->create([
                'user_id' => $user->id,
                'option_id' => $optionId,
            ]);
        }

        $comment->load(['reactions.user:id,name', 'pollVotes.user:id,name']);

        /** @var Model|null $commentable */
        $commentable = $comment->commentable;

        $canModerate = $commentable !== null
            && Gate::forUser($user)->allows('update', $commentable);
        $request->attributes->set('comment_can_moderate', $canModerate);

        $payload = new CommentResource($comment)->resolve($request);

        $alias = Commentables::aliasFor($comment->commentable);
        if ($alias !== null) {
            CommentBroadcast::dispatch("comments.{$alias}.{$comment->commentable_id}", 'poll', $payload);
        }

        return $this->jsonSuccess(new CommentResource($comment));
    }
}
