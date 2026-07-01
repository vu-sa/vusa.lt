<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 *
 * Threaded comment shape consumed by the discussion frontend. Permission flags
 * are computed cheaply: `update` is author-only and `delete` adds a single
 * per-request moderation flag (Gate::allows('update', $parent) evaluated once
 * in the controller and stashed on the request) rather than evaluating the
 * policy for every comment in a thread.
 */
class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $isAuthor = $userId !== null && $this->user_id === $userId;
        $canModerate = (bool) $request->attributes->get('comment_can_moderate', false);

        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'thread_root_id' => $this->thread_root_id,
            'kind' => $this->kind->value,
            'body' => $this->body,
            'metadata' => $this->metadata,
            'user' => [
                'id' => (string) $this->user_id,
                'name' => $this->user?->name,
                'profile_photo_path' => $this->user?->profile_photo_path,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'edited_at' => $this->edited_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'resolved_by' => $this->resolved_by,
            'is_resolved' => $this->isResolved(),
            'mentioned_user_ids' => $this->mentioned_user_ids ?? [],
            'reactions' => $this->reactionsTally($userId),
            'poll' => $this->when($this->isPoll(), fn () => $this->pollPayload($userId)),
            // Only emit replies when the relation is actually loaded. Wrapping an
            // unloaded relation (a MissingValue) in a resource collection blows up
            // when the array is serialized directly (e.g. a broadcast payload).
            'replies' => $this->when(
                $this->relationLoaded('replies'),
                fn () => self::collection($this->replies),
            ),
            'can' => [
                'update' => $isAuthor,
                'delete' => $isAuthor || $canModerate,
                // Reaching this endpoint already required `view` on the parent,
                // and resolve follows the view audience.
                'resolve' => $userId !== null,
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

    /**
     * Live poll state: the stored definition plus per-option tallies and the
     * voters behind them. `my_option_ids` is convenience for the requesting
     * user; receiving clients recompute it from `voters` on broadcast (the
     * broadcast payload has no per-user truth, same as `reacted_by_me`).
     *
     * @return array<string, mixed>
     */
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
