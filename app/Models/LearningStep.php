<?php

namespace App\Models;

use App\Enums\LearningStepKind;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, LearningAudience> $audiences
 * @property string $id
 * @property string|null $key
 * @property bool $is_system
 * @property LearningStepKind $kind
 * @property array|string|null $title
 * @property array|string|null $description
 * @property array|string|null $action_label
 * @property array|string|null $action_url
 * @property bool $per_term
 * @property int $order
 * @property bool $is_active
 * @property Carbon|null $reviewed_at
 * @property string|null $created_by
 * @property-read Collection<int, LearningProgress> $progress
 * @property-read User|null $createdBy
 */
#[Fillable(['key', 'is_system', 'kind', 'title', 'description', 'action_label', 'action_url', 'per_term', 'order', 'is_active', 'reviewed_at', 'created_by'])]
class LearningStep extends Model
{
    use HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    protected static function booted(): void
    {
        static::deleting(function (self $item): void {
            $item->audiences()->get()->each->delete();
            $item->progress()->delete();
        });
    }

    public $translatable = ['title', 'description', 'action_label', 'action_url'];

    /** @return list<string> */
    protected function sanitizedHtmlTranslations(): array
    {
        return ['description'];
    }

    /** @return MorphMany<LearningProgress, $this> */
    public function progress(): MorphMany
    {
        return $this->morphMany(LearningProgress::class, 'progressable');
    }

    /** @return MorphMany<LearningAudience, $this> */
    public function audiences(): MorphMany
    {
        return $this->morphMany(LearningAudience::class, 'audienceable')->orderBy('order')->orderBy('id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return ['kind' => LearningStepKind::class, 'is_system' => 'boolean', 'per_term' => 'boolean', 'is_active' => 'boolean', 'reviewed_at' => 'datetime', 'order' => 'integer'];
    }

    /** @param Builder<static> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<static> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('order')->orderBy('id');
    }
}
