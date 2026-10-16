<?php

namespace App\Models;

use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $mailing_list_id
 * @property string $email
 * @property string|null $note
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read MailingList $mailingList
 *
 * @method static \Database\Factories\MailingListExclusionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListExclusion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListExclusion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListExclusion query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['mailing_list_id', 'email', 'note', 'created_by'])]
class MailingListExclusion extends Model
{
    use HasFactory, HasUlids, LogsModelActivity;

    /** @return BelongsTo<MailingList, $this> */
    public function mailingList(): BelongsTo
    {
        return $this->belongsTo(MailingList::class);
    }

    /** @return Attribute<string, string> */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value) => strtolower(trim($value)));
    }
}
