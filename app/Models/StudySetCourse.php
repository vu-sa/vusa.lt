<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\StudySetCourseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, LecturerReview> $reviews
 * @property-read StudySet|null $studySet
 * @property-read mixed $translations
 * @property-read array|string $name
 *
 * @method static \Database\Factories\StudySetCourseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySetCourse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySetCourse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySetCourse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySetCourse whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySetCourse whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySetCourse whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySetCourse whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class StudySetCourse extends Model
{
    /** @use HasFactory<StudySetCourseFactory> */
    use HasFactory, HasTranslations, HasUlids;

    public $translatable = ['name'];

    protected $fillable = [
        'study_set_id',
        'name',
        'order',
        'semester',
        'credits',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function studySet(): BelongsTo
    {
        return $this->belongsTo(StudySet::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(LecturerReview::class);
    }
}
