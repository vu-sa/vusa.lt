<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A cached copy of one archived post from HyperKitty. Plain text only, shown as text.
 *
 * @property int $id
 * @property string $mailing_list_id
 * @property string $message_id_hash
 * @property string|null $thread_id
 * @property string|null $subject
 * @property string|null $sender_name
 * @property string|null $sender_email
 * @property Carbon|null $sent_at
 * @property string|null $body_text
 * @property array<array-key, mixed>|null $attachments
 * @property string|null $url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MailingList $mailingList
 *
 * @method static \Database\Factories\MailingListMessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListMessage query()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'mailing_list_id',
    'message_id_hash',
    'thread_id',
    'subject',
    'sender_name',
    'sender_email',
    'sent_at',
    'body_text',
    'attachments',
    'url',
])]
class MailingListMessage extends Model
{
    use HasFactory;

    /** @return BelongsTo<MailingList, $this> */
    public function mailingList(): BelongsTo
    {
        return $this->belongsTo(MailingList::class);
    }

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'attachments' => 'array',
        ];
    }
}
