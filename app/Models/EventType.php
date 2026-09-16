<?php

namespace App\Models;

use App\Contracts\GuardsForceDelete;
use App\Models\Traits\GuardsForceDeleteWhenReferenced;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property array|string $name
 * @property string $slug
 * @property array|string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Calendar> $calendarEvents
 * @property-read string|null $force_delete_blocked_reason
 * @property-read array $translatable_columns_from
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\EventTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventType whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventType whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventType whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventType whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventType withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventType withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'name',
    'slug',
    'description',
    'is_active',
    'sort_order',
])]
class EventType extends Model implements GuardsForceDelete
{
    use GuardsForceDeleteWhenReferenced, HasFactory, HasTranslations, SoftDeletes;

    public $translatable = ['name', 'description'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('all-event-types-for-inertia'));
        static::deleted(fn () => Cache::forget('all-event-types-for-inertia'));
        static::restored(fn () => Cache::forget('all-event-types-for-inertia'));
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(Calendar::class);
    }

    /**
     * `calendar.event_type_id` restricts deletes — the FK itself is `nullOnDelete`, so a
     * force-delete would silently un-type every event still wearing this type rather than
     * failing outright.
     */
    public function forceDeleteBlockedReason(): ?string
    {
        return $this->forceDeleteReasonFor([
            'entities.calendar.model' => $this->countedRelation('calendarEvents'),
        ]);
    }
}
