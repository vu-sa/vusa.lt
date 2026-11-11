<?php

namespace App\Http\Resources;

use App\Actions\ResolveCommentAuthorDuty;
use App\Enums\CommentKind;
use App\Models\Comment;
use App\Support\Media\ImageData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @mixin Comment */
class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $isAuthor = $userId !== null && $this->user_id === $userId;
        $user = $request->user();
        $canModerate = (bool) $request->attributes->get('comment_can_moderate', false);
        $isErased = $this->isErased();

        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'thread_root_id' => $this->thread_root_id,
            'kind' => $this->kind->value,
            'body' => $this->body,
            'title' => $this->title,
            'author_duty' => $this->authorDutiable === null ? null : ResolveCommentAuthorDuty::data($this->authorDutiable),
            'images' => $this->getMedia(Comment::IMAGES)->sortBy('order_column')
                ->map(fn (Media $media): array => ImageData::fromMedia($media))->values()->all(),
            // A placeholder: nothing of the comment or its author remains to show.
            'is_erased' => $isErased,
            'metadata' => $this->metadata,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user?->name,
                'profile_photo_path' => $this->user?->profile_photo_path,
                'pronouns' => $this->user?->getTranslations('pronouns') ?: null,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'edited_at' => $this->edited_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'resolved_by' => $this->resolved_by,
            // Transitional(topic-closed): remove is_resolved when all discussion clients read is_closed.
            'is_resolved' => $this->isResolved(),
            'is_closed' => $this->isResolved(),
            'mentioned_user_ids' => $this->mentioned_user_ids ?? [],
            'reactions' => $this->reactionsTally($userId),
            'poll' => $this->when($this->isPoll(), fn () => $this->pollPayload($userId)),
            // Broadcast payloads do not load a thread; never serialize an unloaded relation.
            'replies' => $this->when(
                $this->relationLoaded('threadReplies'),
                fn () => self::collection($this->threadReplies),
            ),
            'can' => [
                'update' => ! $isErased && $user !== null && $this->isEditableBy($user),
                'delete' => ! $isErased && ($isAuthor || $canModerate),
                'resolve' => ! $isErased && $this->kind === CommentKind::Topic && ($isAuthor || $canModerate),
            ],
        ];
    }

    /**
     * Aggregate the loaded reactions into per-emoji tallies.
     *
     * @return array<int, array{emoji: string, count: int, reacted_by_me: bool, users: array<int, array{id: string, name: string|null}>}>
     */
    protected function reactionsTally(?string $userId): array
    {
        if (! $this->relationLoaded('reactions')) {
            return [];
        }

        return $this->reactions
            ->groupBy('emoji')
            ->map(fn ($group, string $emoji) => [
                'emoji' => $emoji,
                'count' => $group->count(),
                'reacted_by_me' => $userId !== null && $group->contains('user_id', $userId),
                'users' => $group->map(fn ($reaction) => [
                    'id' => (string) $reaction->user_id,
                    'name' => $reaction->user?->name,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    protected function pollPayload(?string $userId): array
    {
        $options = $this->pollOptions();
        $votes = $this->relationLoaded('pollVotes') ? $this->pollVotes : collect();
        $byOption = $votes->groupBy('option_id');

        $tallies = collect($options)->map(fn (array $option) => [
            'option_id' => $option['id'],
            'count' => $byOption->get($option['id'], collect())->count(),
            'voters' => $byOption->get($option['id'], collect())->map(fn ($vote) => [
                'id' => (string) $vote->user_id,
                'name' => $vote->user?->name,
            ])->values()->all(),
        ])->values()->all();

        return [
            'options' => collect($options)->map(fn (array $o) => [
                'id' => $o['id'],
                'label' => $o['label'],
            ])->values()->all(),
            'allow_multiple' => $this->pollAllowsMultiple(),
            'closes_at' => $this->pollClosesAt()?->toISOString(),
            'is_closed' => $this->pollIsClosed(),
            'total_votes' => $votes->pluck('user_id')->unique()->count(),
            'tallies' => $tallies,
            'my_option_ids' => $userId === null
                ? []
                : $votes->where('user_id', $userId)->pluck('option_id')->values()->all(),
        ];
    }
}
