<?php

namespace App\Models;

use App\Enums\KnowledgeSectionKind;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property array|string $title
 * @property array|string|null $description
 * @property KnowledgeSectionKind $kind
 * @property Carbon|null $date
 * @property int $order
 * @property int|null $tenant_id
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, KnowledgeItem> $items
 * @property-read Tenant|null $tenant
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\KnowledgeSectionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeSection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeSection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeSection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeSection whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeSection whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeSection whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeSection whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeSection whereTranslationLike(array|string $columns, string $search, ?array $locales = null)
 *
 * @mixin \Eloquent
 */
#[Fillable(['title', 'description', 'kind', 'date', 'order', 'tenant_id', 'created_by'])]
class KnowledgeSection extends Model
{
    use HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    public $translatable = ['title', 'description'];

    #[\Override]
    protected $attributes = ['kind' => 'topic'];

    /** @return HasMany<KnowledgeItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(KnowledgeItem::class)->orderBy('order')->orderBy('id');
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected function casts(): array
    {
        return ['kind' => KnowledgeSectionKind::class, 'date' => 'date', 'order' => 'integer'];
    }
}
