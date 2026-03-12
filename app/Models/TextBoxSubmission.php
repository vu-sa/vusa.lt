<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $content_part_id
 * @property string $text
 * @property string|null $user_id
 * @property string|null $ip_address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ContentPart $contentPart
 * @property-read User|null $user
 *
 * @method static \Database\Factories\TextBoxSubmissionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBoxSubmission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBoxSubmission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBoxSubmission query()
 *
 * @mixin \Eloquent
 */
class TextBoxSubmission extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    public function contentPart(): BelongsTo
    {
        return $this->belongsTo(ContentPart::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
