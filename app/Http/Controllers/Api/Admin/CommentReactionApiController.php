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
 * Toggle one-tap emoji reactions on a comment. Anyone who can view the parent
 * may react.
 */
class CommentReactionApiController extends ApiController
{
    public function toggle(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('react', $comment);

        $validated = $request->validate([
            'emoji' => ['required', 'string', Rule::in(Comment::ALLOWED_REACTIONS)],
        ]);

        $user = $request->user();

        $existing = $comment->reactions()
            ->where('user_id', $user->id)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $comment->reactions()->create([
                'user_id' => $user->id,
                'emoji' => $validated['emoji'],
            ]);
        }

        $comment->load(['reactions.user:id,name']);

        $canModerate = $comment->commentable !== null
            && Gate::forUser($user)->allows('update', $comment->commentable);
        $request->attributes->set('comment_can_moderate', $canModerate);

        $payload = (new CommentResource($comment))->resolve($request);

        $alias = Commentables::aliasFor($comment->commentable);
        if ($alias !== null) {
            CommentBroadcast::dispatch("comments.{$alias}.{$comment->commentable_id}", 'reaction', $payload);
        }

        return $this->jsonSuccess(new CommentResource($comment));
    }
}
