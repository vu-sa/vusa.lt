<?php

namespace App\Models;

use App\Models\Pivots\Dutiable;
use App\Models\Traits\LogsModelActivity;
use App\Services\HtmlSanitizerService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $duty_id
 * @property string $user_id
 * @property string|null $dutiable_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Duty $duty
 * @property-read User $author
 * @property-read Dutiable|null $dutiable
 */
#[Fillable(['duty_id', 'user_id', 'dutiable_id', 'body'])]
class HandoverNote extends Model
{
    use HasFactory, HasUlids, LogsModelActivity;

    /** @return BelongsTo<Duty, $this> */
    public function duty(): BelongsTo
    {
        return $this->belongsTo(Duty::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Dutiable, $this> */
    public function dutiable(): BelongsTo
    {
        return $this->belongsTo(Dutiable::class);
    }

    protected function body(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => app(HtmlSanitizerService::class)->sanitizeCommentBody($value));
    }
}
