<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Tenant|null $tenant
 * @property-read Collection<int, LearningStep> $steps
 * @property string $id
 * @property array|string|null $title
 * @property array|string|null $description
 * @property int|null $tenant_id
 * @property int $order
 */
#[Fillable(['title', 'description', 'order', 'tenant_id', 'created_by'])]
class LearningSection extends Model
{
    use HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    public $translatable = ['title', 'description'];

    /** @return HasMany<LearningStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(LearningStep::class)->orderBy('order')->orderBy('id');
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected function casts(): array
    {
        return ['order' => 'integer'];
    }
}
