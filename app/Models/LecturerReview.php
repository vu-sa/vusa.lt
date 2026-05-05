<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\LecturerReviewFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read StudySetCourse|null $course
 * @property-read array $translatable_columns_from
 * @property-read mixed $translations
 * @property-read array|string $lecturer
 * @property-read array|string $comment
 *
 * @method static \Database\Factories\LecturerReviewFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturerReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturerReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturerReview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturerReview whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturerReview whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturerReview whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturerReview whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class LecturerReview extends Model
{
    /** @use HasFactory<LecturerReviewFactory> */
    use HasFactory, HasTranslations, HasUlids;

    public $translatable = ['lecturer', 'comment'];

    protected $fillable = [
        'study_set_course_id',
        'lecturer',
        'comment',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(StudySetCourse::class, 'study_set_course_id');
    }
}
