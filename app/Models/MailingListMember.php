<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The last known roster of a list, rewritten on every import and sync. `reasons` is null for
 * an unmanaged list, where the app does not know why someone is subscribed.
 *
 * @property int $id
 * @property string $mailing_list_id
 * @property string $email
 * @property string|null $display_name
 * @property string|null $user_id
 * @property list<string>|null $reasons
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MailingList $mailingList
 * @property-read User|null $user
 *
 * @method static \Database\Factories\MailingListMemberFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListMember query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['mailing_list_id', 'email', 'display_name', 'user_id', 'reasons'])]
class MailingListMember extends Model
{
    use HasFactory;

    /** @return BelongsTo<MailingList, $this> */
    public function mailingList(): BelongsTo
    {
        return $this->belongsTo(MailingList::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['reasons' => 'array'];
    }
}
