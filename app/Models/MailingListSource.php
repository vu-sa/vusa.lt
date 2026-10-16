<?php

namespace App\Models;

use App\Actions\ResolveMailingListMembers;
use App\Enums\MailingListAddressMode;
use App\Enums\MailingListSourceType;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One layer of a managed list's membership; see {@see ResolveMailingListMembers}.
 *
 * `filters` keys: `tenant_scope` (MailingListTenantScope), `tenant_ids`, `include_subtypes`,
 * `exclude_institution_type_ids`, `duty_type_ids`.
 *
 * @property string $id
 * @property string $mailing_list_id
 * @property MailingListSourceType $type
 * @property string|null $source_id
 * @property array<string, mixed>|null $filters
 * @property MailingListAddressMode $address_mode
 * @property string|null $email
 * @property string|null $display_name
 * @property string|null $note
 * @property Carbon|null $expires_on
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read MailingList $mailingList
 *
 * @method static \Database\Factories\MailingListSourceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListSource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListSource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListSource query()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'mailing_list_id',
    'type',
    'source_id',
    'filters',
    'address_mode',
    'email',
    'display_name',
    'note',
    'expires_on',
    'created_by',
])]
class MailingListSource extends Model
{
    use HasFactory, HasUlids, LogsModelActivity;

    /** @return BelongsTo<MailingList, $this> */
    public function mailingList(): BelongsTo
    {
        return $this->belongsTo(MailingList::class);
    }

    public function isExpired(?string $date = null): bool
    {
        return $this->expires_on !== null && $this->expires_on->toDateString() < ($date ?? today()->toDateString());
    }

    /**
     * @return mixed
     */
    public function filter(string $key, mixed $default = null)
    {
        return ($this->filters ?? [])[$key] ?? $default;
    }

    protected function casts(): array
    {
        return [
            'type' => MailingListSourceType::class,
            'address_mode' => MailingListAddressMode::class,
            'filters' => 'array',
            'expires_on' => 'date',
        ];
    }
}
