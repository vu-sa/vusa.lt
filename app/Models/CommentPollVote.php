<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $comment_id
 * @property string $user_id
 * @property string $option_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Comment|null $comment
 * @property-read User|null $user
 *
 * @method static \Database\Factories\CommentPollVoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentPollVote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentPollVote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentPollVote query()
 *
 * @mixin \Eloquent
 */
class CommentPollVote extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
