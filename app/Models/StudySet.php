<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\StudySetFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property array|string $name
 * @property array|string|null $description
 * @property int $order
 * @property bool $is_visible
 * @property int $tenant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, StudySetCourse> $courses
 * @property-read int $total_credits
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, LecturerReview> $reviews
 * @property-read Tenant $tenant
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\StudySetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySet whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySet whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySet whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySet whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class StudySet extends Model
{
    /** @use HasFactory<StudySetFactory> */
    use HasFactory, HasTranslations, HasUlids;

    public $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'description',
        'order',
        'is_visible',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(StudySetCourse::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(LecturerReview::class, StudySetCourse::class);
    }

    public function getTotalCreditsAttribute(): int
    {
        return $this->courses->sum('credits');
    }
}
