<?php

namespace App\Models;

use App\Enums\MailingListMode;
use App\Enums\MailingListSyncStatus;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A Mailman list ("konferencija") on konf.vusa.lt. Mailman delivers, moderates and archives;
 * on a managed list the app decides who is subscribed.
 *
 * @property string $id
 * @property int $tenant_id
 * @property string $address
 * @property string|null $mailman_list_id
 * @property string $display_name
 * @property string|null $description
 * @property MailingListMode $mode
 * @property string|null $archive_policy
 * @property int $member_count
 * @property int $posts_last_year
 * @property Carbon|null $last_post_at
 * @property Carbon|null $last_synced_at
 * @property MailingListSyncStatus|null $last_sync_status
 * @property string|null $last_sync_error
 * @property Carbon|null $archive_fetched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $archive_head_hash
 * @property int $archive_backfill_offset
 * @property int $archive_backfill_total
 * @property Carbon|null $archive_backfilled_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Collection<int, MailingListExclusion> $exclusions
 * @property-read Collection<int, MailingListMember> $members
 * @property-read Collection<int, MailingListMessage> $messages
 * @property-read Collection<int, MailingListSource> $sources
 * @property-read Tenant $tenant
 *
 * @method static \Database\Factories\MailingListFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingList query()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'tenant_id',
    'address',
    'mailman_list_id',
    'display_name',
    'description',
    'mode',
    'archive_policy',
    'member_count',
    'posts_last_year',
    'last_post_at',
    'last_synced_at',
    'last_sync_status',
    'last_sync_error',
    'archive_fetched_at',
    'archive_head_hash',
    'archive_backfill_offset',
    'archive_backfill_total',
    'archive_backfilled_at',
])]
class MailingList extends Model
{
    use HasFactory, HasUlids, LogsModelActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['display_name', 'description', 'tenant_id', 'mode'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<MailingListSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(MailingListSource::class)->orderBy('created_at');
    }

    /** @return HasMany<MailingListExclusion, $this> */
    public function exclusions(): HasMany
    {
        return $this->hasMany(MailingListExclusion::class)->orderBy('email');
    }

    /** @return HasMany<MailingListMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(MailingListMember::class)->orderBy('email');
    }

    /** @return HasMany<MailingListMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(MailingListMessage::class)->orderByDesc('sent_at')->orderByDesc('id');
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function withSyncLock(callable $callback): mixed
    {
        return Cache::lock('mailing-list:'.$this->id, 900)->block(2, $callback);
    }

    public function isManaged(): bool
    {
        return $this->mode === MailingListMode::Managed;
    }

    public function mailmanListId(): string
    {
        return $this->mailman_list_id ?? self::listIdFor($this->address);
    }

    /** Mailman's list id: the address with "@" swapped for ".". */
    public static function listIdFor(string $address): string
    {
        return str_replace('@', '.', strtolower($address));
    }

    protected function casts(): array
    {
        return [
            'mode' => MailingListMode::class,
            'last_sync_status' => MailingListSyncStatus::class,
            'member_count' => 'integer',
            'posts_last_year' => 'integer',
            'last_post_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'archive_fetched_at' => 'datetime',
            'archive_backfilled_at' => 'datetime',
            'archive_backfill_offset' => 'integer',
            'archive_backfill_total' => 'integer',
        ];
    }
}
