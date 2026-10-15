<?php

namespace App\Models;

use App\Enums\CommentKind;
use App\Services\HtmlSanitizerService;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property string $id
 * @property string|null $parent_id
 * @property string|null $thread_root_id
 * @property string $commentable_type
 * @property string $commentable_id
 * @property string|null $user_id
 * @property CommentKind $kind
 * @property string $body
 * @property array<array-key, mixed>|null $metadata
 * @property array<array-key, mixed>|null $mentioned_user_ids
 * @property Carbon|null $resolved_at
 * @property string|null $resolved_by
 * @property Carbon|null $edited_at
 * @property Carbon|null $erased_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|\Eloquent $commentable
 * @property-read Comment|null $parent
 * @property-read Collection<int, CommentPollVote> $pollVotes
 * @property-read Collection<int, CommentReaction> $reactions
 * @property-read Collection<int, Comment> $replies
 * @property-read User|null $resolver
 * @property-read Comment|null $threadRoot
 * @property-read User|null $user
 *
 * @method static \Database\Factories\CommentFactory factory($count = null, $state = [])
 * @method static Builder<static>|Comment forCommentable(string $type, string $id)
 * @method static Builder<static>|Comment newModelQuery()
 * @method static Builder<static>|Comment newQuery()
 * @method static Builder<static>|Comment notErased()
 * @method static Builder<static>|Comment query()
 * @method static Builder<static>|Comment resolved()
 * @method static Builder<static>|Comment roots()
 * @method static Builder<static>|Comment unresolved()
 *
 * @mixin \Eloquent
 */
#[Touches(['commentable'])]
#[Unguarded]
class Comment extends Model
{
    use HasFactory, HasUlids;

    /**
     * The emoji a user may react to a comment with.
     */
    public const ALLOWED_REACTIONS = ['👍', '❤️', '✅', '🎉', '👀', '🙏'];

    /**
     * Mirror the DB default so freshly-created in-memory models carry a kind
     * (the column default only applies on the persisted row, not the instance).
     *
     * @var array<string, mixed>
     */
    #[\Override]
    protected $attributes = [
        'kind' => 'comment',
    ];

    #[\Override]
    protected $with = ['user:id,name,profile_photo_path'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'kind' => CommentKind::class,
            'metadata' => 'array',
            'mentioned_user_ids' => 'array',
            'resolved_at' => 'datetime',
            'edited_at' => 'datetime',
            'erased_at' => 'datetime',
        ];
    }

    /**
     * The body is user-authored HTML from the Tiptap editor. Sanitize it on the
     * way in so it can be rendered with `v-html` without allowing script injection.
     */
    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => static::sanitizeBody($value),
        );
    }

    /**
     * Strip disallowed markup from a comment body, keeping the editor's
     * formatting, links and @mention spans.
     */
    public static function sanitizeBody(string $html): string
    {
        return app(HtmlSanitizerService::class)->sanitizeCommentBody($html);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    public function threadRoot(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'thread_root_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    public function pollVotes(): HasMany
    {
        return $this->hasMany(CommentPollVote::class);
    }

    public function isErased(): bool
    {
        return $this->erased_at !== null;
    }

    /**
     * Deleting a comment erases what it said and who said it, for good, but keeps its row so the
     * thread shows "Komentaras ištrintas" in its place and its replies stay where they were.
     */
    public function erase(): void
    {
        DB::transaction(function (): void {
            $this->reactions()->delete();
            $this->pollVotes()->delete();

            $this->forceFill([
                'kind' => CommentKind::Comment,
                'body' => '',
                'metadata' => null,
                'mentioned_user_ids' => null,
                'user_id' => null,
                'edited_at' => null,
                'erased_at' => now(),
            ])->save();
        });

        $this->unsetRelation('user')->unsetRelation('reactions')->unsetRelation('pollVotes');
    }

    public function isPoll(): bool
    {
        return $this->kind === CommentKind::Poll;
    }

    /**
     * The poll's options as stored in metadata.
     *
     * @return array<int, array{id: string, label: string}>
     */
    public function pollOptions(): array
    {
        $options = $this->metadata['poll']['options'] ?? [];

        return is_array($options) ? array_values($options) : [];
    }

    public function pollAllowsMultiple(): bool
    {
        return (bool) ($this->metadata['poll']['allow_multiple'] ?? false);
    }

    public function pollClosesAt(): ?Carbon
    {
        $closesAt = $this->metadata['poll']['closes_at'] ?? null;

        return $closesAt ? Carbon::parse($closesAt) : null;
    }

    public function pollIsClosed(): bool
    {
        $closesAt = $this->pollClosesAt();

        return $closesAt !== null && $closesAt->isPast();
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * @param  Builder<Comment>  $query
     */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * @param  Builder<Comment>  $query
     */
    public function scopeNotErased(Builder $query): void
    {
        $query->whereNull('erased_at');
    }

    /**
     * @param  Builder<Comment>  $query
     */
    public function scopeForCommentable(Builder $query, string $type, string $id): void
    {
        $query->where('commentable_type', $type)->where('commentable_id', $id);
    }

    /**
     * @param  Builder<Comment>  $query
     */
    public function scopeResolved(Builder $query): void
    {
        $query->whereNotNull('resolved_at');
    }

    /**
     * @param  Builder<Comment>  $query
     */
    public function scopeUnresolved(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function resolve(User $user): void
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by' => $user->id,
        ]);
    }

    public function unresolve(): void
    {
        $this->update([
            'resolved_at' => null,
            'resolved_by' => null,
        ]);
    }

    public function markEdited(): void
    {
        $this->edited_at = now();
    }

    public function isAuthor(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Extract mentioned user ULIDs from Tiptap mention nodes in an HTML body.
     * Mention spans carry the user id in a `data-id` attribute (matching the
     * notes editor's Mention extension output).
     *
     * @return array<int, string>
     */
    public static function extractMentions(string $html): array
    {
        if (! preg_match_all('/data-id="([^"]+)"/', $html, $matches)) {
            return [];
        }

        return array_values(array_unique($matches[1]));
    }
}
