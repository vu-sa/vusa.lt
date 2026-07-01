<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\CommentBroadcast;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Support\Commentables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Cast (or retract) a vote on a poll comment. Anyone who can view the parent may
 * vote. Toggle semantics mirror reactions:
 *
 *  - single-choice: selecting your current option retracts it; another option replaces it.
 *  - multiple-choice: each option toggles independently.
 */
class CommentPollVoteApiController extends ApiController
{
    public function toggle(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('vote', $comment);

        abort_unless($comment->isPoll(), 422, 'This comment is not a poll.');
        abort_if($comment->pollIsClosed(), 422, 'This poll is closed.');

        $optionIds = collect($comment->pollOptions())->pluck('id')->all();

        $validated = $request->validate([
            'option_id' => ['required', 'string', Rule::in($optionIds)],
        ]);

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

        $canModerate = $comment->commentable !== null
            && Gate::forUser($user)->allows('update', $comment->commentable);
        $request->attributes->set('comment_can_moderate', $canModerate);

        $payload = (new CommentResource($comment))->resolve($request);

        $alias = Commentables::aliasFor($comment->commentable);
        if ($alias !== null) {
            CommentBroadcast::dispatch("comments.{$alias}.{$comment->commentable_id}", 'poll', $payload);
        }

        return $this->jsonSuccess(new CommentResource($comment));
    }
}
