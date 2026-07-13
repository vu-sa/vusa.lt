<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Commentable;
use App\Enums\CommentKind;
use App\Events\CommentBroadcast;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Comments\StoreCommentRequest;
use App\Http\Requests\Comments\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Services\CommentableMentionResolver;
use App\Support\Commentables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * The discussion framework API: threaded comments attached to any allowlisted
 * commentable (meetings, agenda items, institutions, …). Read and write follow
 * the parent's `view` ability ("see it → discuss it").
 */
class CommentApiController extends ApiController
{
    public function __construct(protected CommentableMentionResolver $mentionResolver) {}

    /**
     * Threaded list of root comments (with their replies) for a commentable.
     */
    public function index(Request $request, string $commentableType, string $commentableId): JsonResponse
    {
        $commentable = $this->resolveCommentable($commentableType, $commentableId);
        $this->authorize('view', $commentable);

        $this->stashModeration($request, $commentable);

        $replyLoad = ['user:id,name,profile_photo_path', 'reactions.user:id,name'];

        $query = Comment::forCommentable($commentable::class, $commentable->getKey())
            ->roots()
            ->with([
                'reactions.user:id,name',
                'pollVotes.user:id,name',
                'replies' => fn ($q) => $q->with($replyLoad)->orderBy('created_at'),
            ])
            ->orderBy('created_at');

        if ($request->filled('resolved')) {
            $request->boolean('resolved') ? $query->resolved() : $query->unresolved();
        }

        return $this->jsonSuccess(CommentResource::collection($query->get()));
    }

    /**
     * Post a new comment or threaded reply.
     */
    public function store(StoreCommentRequest $request, string $commentableType, string $commentableId): JsonResponse
    {
        $commentable = $this->resolveCommentable($commentableType, $commentableId);
        $this->authorize('view', $commentable);

        $attributes = [];

        if ($request->input('kind') === CommentKind::Poll->value) {
            $attributes = [
                'kind' => CommentKind::Poll,
                'metadata' => ['poll' => $this->normalizePoll($request->input('metadata.poll'))],
            ];
        }

        /** @var Comment $comment */
        $comment = $commentable->comment(
            $request->input('body'),
            $request->input('parent_id'),
            $attributes,
        );

        $comment->load(['reactions.user:id,name', 'pollVotes.user:id,name']);
        $this->stashModeration($request, $commentable);

        $this->broadcast($commentableType, $commentableId, 'created', $this->payload($request, $comment));

        return $this->jsonCreated(new CommentResource($comment));
    }

    /**
     * The @mention pool for a commentable: users who can already view it.
     */
    public function mentionables(Request $request, string $commentableType, string $commentableId): JsonResponse
    {
        $commentable = $this->resolveCommentable($commentableType, $commentableId);
        $this->authorize('view', $commentable);

        return $this->jsonSuccess($this->mentionResolver->resolve($commentable));
    }

    /**
     * Edit your own comment.
     */
    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $comment->body = $request->input('body');
        $comment->mentioned_user_ids = Comment::extractMentions($comment->body);
        $comment->markEdited();
        $comment->save();

        $comment->load(['reactions.user:id,name', 'pollVotes.user:id,name']);
        $this->stashModeration($request, $comment->commentable);

        $this->broadcastForComment($comment, 'updated', $this->payload($request, $comment));

        return $this->jsonSuccess(new CommentResource($comment));
    }

    /**
     * Delete a comment (author, or a moderator who can update the parent).
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $this->broadcastForComment($comment, 'deleted', ['id' => $comment->id]);
        $comment->delete();

        return $this->jsonSuccess(['id' => $comment->id], 'Komentaras ištrintas.');
    }

    public function resolve(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('resolve', $comment);
        $comment->resolve($request->user());

        $comment->load(['reactions.user:id,name', 'pollVotes.user:id,name']);
        $this->stashModeration($request, $comment->commentable);
        $this->broadcastForComment($comment, 'resolved', $this->payload($request, $comment));

        return $this->jsonSuccess(new CommentResource($comment));
    }

    public function unresolve(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('resolve', $comment);
        $comment->unresolve();

        $comment->load(['reactions.user:id,name', 'pollVotes.user:id,name']);
        $this->stashModeration($request, $comment->commentable);
        $this->broadcastForComment($comment, 'resolved', $this->payload($request, $comment));

        return $this->jsonSuccess(new CommentResource($comment));
    }

    /**
     * "Discussions involving me": comments that @mention the user or that the
     * user authored, scoped to parents the user can still view, newest first.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $this->requireAuth($request);

        $comments = Comment::query()
            ->where(function ($q) use ($user) {
                $q->whereJsonContains('mentioned_user_ids', $user->id)
                    ->orWhere('user_id', $user->id);
            })
            ->with('commentable')
            ->latest()
            ->limit(50)
            ->get()
            ->filter(function (Comment $comment) use ($user) {
                /** @var Model|null $commentable */
                $commentable = $comment->commentable;

                return $commentable !== null
                    && Gate::forUser($user)->allows('view', $commentable);
            })
            ->take(20)
            ->values();

        $data = $comments->map(function (Comment $comment) {
            /** @var Model|null $commentable */
            $commentable = $comment->commentable;

            return [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $comment->created_at?->toISOString(),
                'commentable_type' => Commentables::aliasFor($commentable),
                'commentable_id' => (string) $comment->commentable_id,
                'commentable_name' => $commentable
                    ? ($commentable->getAttribute('name') ?? $commentable->getAttribute('title'))
                    : null,
            ];
        });

        return $this->jsonSuccess($data);
    }

    /**
     * Resolve a commentable from the allowlist or 404.
     */
    protected function resolveCommentable(string $type, string $id): Model&Commentable
    {
        $commentable = Commentables::resolve($type, $id);

        abort_if($commentable === null, 404, 'Commentable not found.');

        return $commentable;
    }

    /**
     * Normalize a validated poll definition: assign a stable server-side id to
     * every option (clients only supply labels) and keep just the known keys.
     *
     * @param  array<string, mixed>  $poll
     * @return array{options: array<int, array{id: string, label: string}>, allow_multiple: bool, closes_at: string|null}
     */
    protected function normalizePoll(array $poll): array
    {
        $options = collect($poll['options'] ?? [])
            ->map(fn ($option) => [
                'id' => (string) Str::ulid(),
                'label' => (string) $option['label'],
            ])
            ->values()
            ->all();

        return [
            'options' => $options,
            'allow_multiple' => (bool) ($poll['allow_multiple'] ?? false),
            'closes_at' => isset($poll['closes_at']) ? Carbon::parse($poll['closes_at'])->toISOString() : null,
        ];
    }

    /**
     * Evaluate the moderation flag once per request and stash it for the
     * resource (avoids per-comment policy evaluation across a thread).
     */
    protected function stashModeration(Request $request, ?Model $commentable): void
    {
        $canModerate = $commentable !== null
            && $request->user() !== null
            && Gate::forUser($request->user())->allows('update', $commentable);

        $request->attributes->set('comment_can_moderate', $canModerate);
    }

    /**
     * Serialize a comment for a broadcast payload (per-user fields are
     * recomputed client-side).
     *
     * @return array<string, mixed>
     */
    protected function payload(Request $request, Comment $comment): array
    {
        // resolve() (not toArray()) filters out MissingValue/when() artifacts and
        // fully resolves nested resources, yielding a clean json-encodable array.
        return (new CommentResource($comment))->resolve($request);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function broadcast(string $type, string $id, string $action, array $payload): void
    {
        CommentBroadcast::dispatch("comments.{$type}.{$id}", $action, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function broadcastForComment(Comment $comment, string $action, array $payload): void
    {
        $alias = Commentables::aliasFor($comment->commentable);

        if ($alias !== null) {
            $this->broadcast($alias, (string) $comment->commentable_id, $action, $payload);
        }
    }
}
