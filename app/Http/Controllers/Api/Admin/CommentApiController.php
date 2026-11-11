<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Media\SyncImageMedia;
use App\Actions\ResolveCommentAuthorDuty;
use App\Contracts\Commentable;
use App\Enums\CommentKind;
use App\Events\CommentBroadcast;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Comments\IndexCommentsRequest;
use App\Http\Requests\Comments\StoreCommentRequest;
use App\Http\Requests\Comments\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Policies\CommentPolicy;
use App\Services\CommentableMentionResolver;
use App\Support\Commentables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
    public function index(IndexCommentsRequest $request, string $commentableType, string $commentableId): JsonResponse
    {
        $commentable = $this->resolveCommentable($commentableType, $commentableId);

        $this->stashModeration($request, $commentable);

        $replyLoad = ['user:id,name,profile_photo_path,pronouns', 'authorDutiable.duty.institution.tenant', 'media', 'reactions.user:id,name', 'pollVotes.user:id,name'];

        $query = Comment::forCommentable($commentable->getMorphClass(), $commentable->getKey())
            ->roots()
            ->with([
                'user:id,name,profile_photo_path,pronouns',
                'authorDutiable.duty.institution.tenant',
                'media',
                'reactions.user:id,name',
                'pollVotes.user:id,name',
                'threadReplies' => fn ($q) => $q
                    ->whereMorphedTo('commentable', $commentable)
                    ->with($replyLoad),
            ])
            ->orderBy('created_at');

        if ($request->validated('resolved') !== null) {
            $request->safe()->boolean('resolved') ? $query->resolved() : $query->unresolved();
        }

        $authorDuty = app(ResolveCommentAuthorDuty::class);

        return $this->jsonSuccess(CommentResource::collection($query->get()), meta: [
            'capabilities' => Commentables::capabilitiesFor($commentableType),
            'me' => [
                'duties' => $authorDuty->options($request->user())->map(fn ($option) => ResolveCommentAuthorDuty::data($option))->values()->all(),
                'default_dutiable_id' => $authorDuty->default($request->user(), $commentable),
                // Lets the client keep moderation actions on comments that arrive over the broadcast.
                'can_moderate' => (bool) $request->attributes->get('comment_can_moderate', false),
            ],
        ]);
    }

    /**
     * Post a new comment or threaded reply.
     */
    public function store(StoreCommentRequest $request, string $commentableType, string $commentableId): JsonResponse
    {
        $commentable = $this->resolveCommentable($commentableType, $commentableId);
        $this->authorize('view', $commentable);

        $kind = CommentKind::from($request->validated('kind') ?? CommentKind::Comment->value);
        $capabilities = Commentables::capabilitiesFor($commentableType);

        if (($kind === CommentKind::Topic && ! $capabilities['topics'])
            || ($kind === CommentKind::Poll && ! $capabilities['polls'])) {
            throw ValidationException::withMessages(['kind' => __('validation.in', ['attribute' => 'kind'])]);
        }

        $comment = DB::transaction(function () use ($request, $commentable, $kind): Comment {
            $user = $request->user();
            $requestedDuty = $request->validated('dutiable_id');
            $dutiableId = app(ResolveCommentAuthorDuty::class)->forPost($user, $commentable, $requestedDuty);
            $attributes = [
                'kind' => $kind,
                'title' => $kind === CommentKind::Topic ? $request->validated('title') : null,
                'author_dutiable_id' => $dutiableId,
            ];

            if ($kind === CommentKind::Poll) {
                $attributes['metadata'] = ['poll' => $this->normalizePoll($request->validated('metadata.poll'))];
            }

            foreach ($request->validated('image_ids') ?? [] as $mediaId) {
                SyncImageMedia::requirePending($user, (int) $mediaId);
            }

            $this->refuseClosedTopic($commentable, $request->validated('parent_id'));

            $comment = $commentable->comment($request->validated('body'), $request->validated('parent_id'), $attributes);

            foreach ($request->validated('image_ids') ?? [] as $mediaId) {
                app(SyncImageMedia::class)->attachPending($comment, Comment::IMAGES, (int) $mediaId, $user);
            }

            if ($requestedDuty !== null && ($user->ui_preferences['comment_dutiable_id'] ?? null) !== $requestedDuty) {
                $user->forceFill(['ui_preferences' => array_merge($user->ui_preferences ?? [], ['comment_dutiable_id' => $requestedDuty])])->saveQuietly();
            }

            return $comment;
        });

        $comment->load(['user:id,name,profile_photo_path,pronouns', 'authorDutiable.duty.institution.tenant', 'media', 'reactions.user:id,name', 'pollVotes.user:id,name']);
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

        $comment->body = $request->validated('body');
        $comment->mentioned_user_ids = Comment::extractMentions($comment->body);
        $comment->markEdited();
        $comment->save();

        $comment->load(['user:id,name,profile_photo_path,pronouns', 'authorDutiable.duty.institution.tenant', 'media', 'reactions.user:id,name', 'pollVotes.user:id,name']);
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

        $comment->erase();
        $this->stashModeration($request, $comment->commentable);

        $this->broadcastForComment($comment, 'updated', $this->payload($request, $comment));

        return $this->jsonSuccess(new CommentResource($comment), $this->entityMessage('deleted', 'comment'));
    }

    public function resolve(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('resolve', $comment);
        $comment->resolve($request->user());

        $comment->load(['user:id,name,profile_photo_path,pronouns', 'authorDutiable.duty.institution.tenant', 'media', 'reactions.user:id,name', 'pollVotes.user:id,name']);
        $this->stashModeration($request, $comment->commentable);
        $this->broadcastForComment($comment, 'resolved', $this->payload($request, $comment));

        return $this->jsonSuccess(new CommentResource($comment));
    }

    public function unresolve(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('resolve', $comment);
        $comment->unresolve();

        $comment->load(['user:id,name,profile_photo_path,pronouns', 'authorDutiable.duty.institution.tenant', 'media', 'reactions.user:id,name', 'pollVotes.user:id,name']);
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
            ->where(function ($q) use ($user): void {
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
     * A closed topic takes no more replies; the root row is locked so a reply cannot slip in while it closes.
     */
    protected function refuseClosedTopic(Model&Commentable $commentable, ?string $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        /** @var Comment|null $parent */
        $parent = $commentable->comments()->find($parentId);
        $root = $parent === null ? null : $commentable->comments()->lockForUpdate()->find($parent->thread_root_id ?? $parent->id);

        if ($root instanceof Comment && $root->kind === CommentKind::Topic && $root->isResolved()) {
            throw ValidationException::withMessages(['parent_id' => __('discussions.topic.closed_hint')]);
        }
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

    /** Avoid per-comment policy queries when serializing a thread. */
    protected function stashModeration(Request $request, ?Model $commentable): void
    {
        $canModerate = $commentable !== null
            && $request->user() !== null
            && app(CommentPolicy::class)->canModerate($request->user(), $commentable);

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
        // resolve() removes conditional fields omitted from broadcast payloads.
        return new CommentResource($comment)->resolve($request);
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
