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
 * @property-read Tenant|null $tenant
 * @property-read Collection<int, KnowledgeItem> $items
 * @property string $id
 * @property array|string|null $title
 * @property array|string|null $description
 * @property KnowledgeSectionKind $kind
 * @property Carbon|null $date
 * @property int|null $tenant_id
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $order
 */
#[Fillable(['title', 'description', 'kind', 'date', 'order', 'tenant_id', 'created_by'])]
class KnowledgeSection extends Model
{
    use HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    public $translatable = ['title', 'description'];

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
