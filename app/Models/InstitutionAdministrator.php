<?php

namespace App\Models;

use App\Services\InstitutionAccessService;
use App\Services\Typesense\TypesenseScopedKeyService;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * A person nominated to look after an institution for one term.
 *
 * An administrator is deliberately not a member of the body: nothing here feeds
 * Institution::users(), duties.current_users, the contacts pages or the search
 * index. They carry the institution's tasks and notifications, nothing else.
 *
 * @property string $id
 * @property string $institution_id
 * @property string $cadence_id
 * @property string $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Cadence|null $cadence
 * @property-read Institution|null $institution
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionAdministrator newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionAdministrator newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionAdministrator query()
 *
 * @mixin \Eloquent
 */
#[Table(name: 'institution_administrators', keyType: 'string')]
#[WithoutIncrementing]
class InstitutionAdministrator extends Pivot
{
    use HasFactory, HasUlids;

    /** @var list<string> */
    protected $fillable = ['id', 'institution_id', 'cadence_id', 'user_id'];

    /**
     * A nomination widens what its user may see (InstitutionAccessService feeds the
     * `.own` scope and the Typesense scoped key), so both caches have to go with it.
     */
    #[\Override]
    protected static function booted(): void
    {
        $invalidate = function (InstitutionAdministrator $administrator): void {
            InstitutionAccessService::invalidateForUser($administrator->user_id);
            TypesenseScopedKeyService::invalidateForUser($administrator->user_id);
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /**
     * @return BelongsTo<Institution, $this>
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * @return BelongsTo<Cadence, $this>
     */
    public function cadence(): BelongsTo
    {
        return $this->belongsTo(Cadence::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
